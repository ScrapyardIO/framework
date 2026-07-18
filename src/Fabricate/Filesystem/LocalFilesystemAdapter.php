<?php

namespace Fabricate\Filesystem;

use Fabricate\NutsAndBolts\Concerns\Conditionable;
use RuntimeException;

class LocalFilesystemAdapter extends FilesystemAdapter
{
    use Conditionable;

    protected string $disk;

    public function providesTemporaryUrls(): bool
    {
        return ! is_null($this->temporaryUrlCallback);
    }

    public function providesTemporaryUploadUrls(): bool
    {
        return ! is_null($this->temporaryUploadUrlCallback);
    }

    public function temporaryUrl($path, $expiration, array $options = [])
    {
        if ($this->temporaryUrlCallback) {
            return $this->temporaryUrlCallback->bindTo($this, static::class)($path, $expiration, $options);
        }

        throw new RuntimeException('This driver does not support creating temporary URLs.');
    }

    public function temporaryUploadUrl($path, $expiration, array $options = []): array
    {
        if ($this->temporaryUploadUrlCallback) {
            return $this->temporaryUploadUrlCallback->bindTo($this, static::class)($path, $expiration, $options);
        }

        throw new RuntimeException('This driver does not support creating temporary upload URLs.');
    }

    public function diskName(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }
}
