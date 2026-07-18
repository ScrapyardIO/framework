<?php

namespace Fabricate\Filesystem;

use Fabricate\Contracts\Filesystem\LockTimeoutException;

class LockableFile
{
    /**
     * @var resource
     */
    protected $handle;

    protected string $path;

    protected bool $isLocked = false;

    public function __construct(string $path, string $mode)
    {
        $this->path = $path;

        $this->ensureDirectoryExists($path);
        $this->createResource($path, $mode);
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (! file_exists(dirname($path))) {
            @mkdir(dirname($path), 0777, true);
        }
    }

    /**
     * @throws \Exception
     */
    protected function createResource(string $path, string $mode): void
    {
        $this->handle = fopen($path, $mode);
    }

    public function read(?int $length = null): string
    {
        clearstatcache(true, $this->path);

        return fread($this->handle, $length ?? ($this->size() ?: 1));
    }

    public function size(): int
    {
        return filesize($this->path);
    }

    public function write(string $contents): static
    {
        fwrite($this->handle, $contents);
        fflush($this->handle);

        return $this;
    }

    public function truncate(): static
    {
        rewind($this->handle);
        ftruncate($this->handle, 0);

        return $this;
    }

    /**
     * @throws LockTimeoutException
     */
    public function getSharedLock(bool $block = false): static
    {
        if (! flock($this->handle, LOCK_SH | ($block ? 0 : LOCK_NB))) {
            throw new LockTimeoutException("Unable to acquire file lock at path [{$this->path}].");
        }

        $this->isLocked = true;

        return $this;
    }

    /**
     * @throws LockTimeoutException
     */
    public function getExclusiveLock(bool $block = false): static
    {
        if (! flock($this->handle, LOCK_EX | ($block ? 0 : LOCK_NB))) {
            throw new LockTimeoutException("Unable to acquire file lock at path [{$this->path}].");
        }

        $this->isLocked = true;

        return $this;
    }

    public function releaseLock(): static
    {
        flock($this->handle, LOCK_UN);
        $this->isLocked = false;

        return $this;
    }

    public function close(): bool
    {
        if ($this->isLocked) {
            $this->releaseLock();
        }

        return fclose($this->handle);
    }
}
