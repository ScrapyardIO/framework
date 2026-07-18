<?php

namespace Fabricate\Filesystem;

use BadMethodCallException;
use Closure;
use Fabricate\Chassis\Chassis;
use Fabricate\Contracts\Debug\ExceptionHandler;
use Fabricate\Contracts\Filesystem\Cloud as CloudFilesystemContract;
use Fabricate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Fabricate\Filesystem\Enums\VISIBILITY;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Concerns\Conditionable;
use Fabricate\NutsAndBolts\Concerns\Macroable;
use Fabricate\NutsAndBolts\Str;
use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToProvideChecksum;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use SplFileInfo;
use Throwable;

/**
 * @mixin \League\Flysystem\FilesystemOperator
 */
class FilesystemAdapter implements CloudFilesystemContract
{
    use Conditionable;
    use Macroable {
        __call as macroCall;
    }

    protected FilesystemOperator $driver;

    protected FlysystemAdapter $adapter;

    protected array $config;

    protected PathPrefixer $prefixer;

    protected ?Closure $temporaryUrlCallback = null;

    protected ?Closure $temporaryUploadUrlCallback = null;

    public function __construct(FilesystemOperator $driver, FlysystemAdapter $adapter, array $config = [])
    {
        $this->driver = $driver;
        $this->adapter = $adapter;
        $this->config = $config;

        $separator = $config['directory_separator'] ?? DIRECTORY_SEPARATOR;
        $this->prefixer = new PathPrefixer($config['root'] ?? '', $separator);

        if (isset($config['prefix'])) {
            $this->prefixer = new PathPrefixer($this->prefixer->prefixPath($config['prefix']), $separator);
        }
    }

    public function exists($path): bool
    {
        return $this->driver->has($path);
    }

    public function missing($path): bool
    {
        return ! $this->exists($path);
    }

    public function fileExists($path): bool
    {
        return $this->driver->fileExists($path);
    }

    public function fileMissing($path): bool
    {
        return ! $this->fileExists($path);
    }

    public function directoryExists($path): bool
    {
        return $this->driver->directoryExists($path);
    }

    public function directoryMissing($path): bool
    {
        return ! $this->directoryExists($path);
    }

    public function path($path): string
    {
        return $this->prefixer->prefixPath($path);
    }

    public function get($path): ?string
    {
        try {
            return $this->driver->read($path);
        } catch (UnableToReadFile $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return null;
        }
    }

    public function json($path, $flags = 0): ?array
    {
        $content = $this->get($path);

        return is_null($content) ? null : json_decode($content, true, 512, $flags);
    }

    /**
     * @param  \Psr\Http\Message\StreamInterface|string|resource  $contents
     * @return bool|string
     */
    public function put($path, $contents, $options = [])
    {
        $options = is_string($options)
            ? ['visibility' => $options]
            : (array) $options;

        try {
            if ($contents instanceof StreamInterface) {
                $this->driver->writeStream($path, $contents->detach(), $options);

                return true;
            }

            is_resource($contents)
                ? $this->driver->writeStream($path, $contents, $options)
                : $this->driver->write($path, $contents, $options);
        } catch (UnableToWriteFile|UnableToSetVisibility $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return false;
        }

        return true;
    }

    /**
     * @param  string|\SplFileInfo|array|null  $file
     */
    public function putFile($path, $file = null, $options = [])
    {
        if (is_null($file) || is_array($file)) {
            [$path, $file, $options] = ['', $path, $file ?? []];
        }

        if (is_string($file)) {
            $file = new SplFileInfo($file);
        }

        return $this->putFileAs($path, $file, $file->getBasename(), $options);
    }

    /**
     * @param  string|\SplFileInfo|array|null  $file
     */
    public function putFileAs($path, $file, $name = null, $options = [])
    {
        if (is_null($name) || is_array($name)) {
            [$path, $file, $name, $options] = ['', $path, $file, $name ?? []];
        }

        $filePath = is_string($file) ? $file : $file->getRealPath();
        $stream = fopen($filePath, 'r');

        $result = $this->put(
            $path = trim($path.'/'.$name, '/'),
            $stream,
            $options
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }

    public function getVisibility($path): string
    {
        if ($this->driver->visibility($path) === Visibility::PUBLIC) {
            return VISIBILITY::PUBLIC->value;
        }

        return VISIBILITY::PRIVATE->value;
    }

    /**
     * @param  string|\Fabricate\Filesystem\Enums\VISIBILITY  $visibility
     */
    public function setVisibility($path, $visibility): bool
    {
        try {
            $this->driver->setVisibility($path, $this->parseVisibility($visibility));
        } catch (UnableToSetVisibility $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return false;
        }

        return true;
    }

    public function prepend($path, $data, $separator = PHP_EOL): bool
    {
        if ($this->fileExists($path)) {
            return (bool) $this->put($path, $data.$separator.$this->get($path));
        }

        return (bool) $this->put($path, $data);
    }

    public function append($path, $data, $separator = PHP_EOL): bool
    {
        if ($this->fileExists($path)) {
            return (bool) $this->put($path, $this->get($path).$separator.$data);
        }

        return (bool) $this->put($path, $data);
    }

    public function delete($paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                $this->driver->delete($path);
            } catch (UnableToDeleteFile $e) {
                if ($this->throwsExceptions()) {
                    throw $e;
                }

                $this->report($e);
                $success = false;
            }
        }

        return $success;
    }

    public function copy($from, $to): bool
    {
        try {
            $this->driver->copy($from, $to);
        } catch (UnableToCopyFile $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return false;
        }

        return true;
    }

    public function move($from, $to): bool
    {
        try {
            $this->driver->move($from, $to);
        } catch (UnableToMoveFile $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return false;
        }

        return true;
    }

    public function size($path)
    {
        return $this->driver->fileSize($path);
    }

    public function checksum(string $path, array $options = [])
    {
        try {
            return $this->driver->checksum($path, $options);
        } catch (UnableToProvideChecksum $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return false;
        }
    }

    public function mimeType($path)
    {
        try {
            return $this->driver->mimeType($path);
        } catch (UnableToRetrieveMetadata $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return false;
        }
    }

    public function lastModified($path)
    {
        return $this->driver->lastModified($path);
    }

    public function readStream($path)
    {
        try {
            return $this->driver->readStream($path);
        } catch (UnableToReadFile $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return null;
        }
    }

    public function writeStream($path, $resource, array $options = []): bool
    {
        try {
            $this->driver->writeStream($path, $resource, $options);
        } catch (UnableToWriteFile|UnableToSetVisibility $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return false;
        }

        return true;
    }

    public function url($path): string
    {
        if (isset($this->config['prefix'])) {
            $path = $this->concatPathToUrl($this->config['prefix'], $path);
        }

        $adapter = $this->adapter;

        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        }

        if (method_exists($this->driver, 'getUrl')) {
            return $this->driver->getUrl($path);
        }

        if ($adapter instanceof FtpAdapter || $adapter instanceof SftpAdapter) {
            return $this->getFtpUrl($path);
        }

        if ($adapter instanceof LocalAdapter) {
            return $this->getLocalUrl($path);
        }

        throw new RuntimeException('This driver does not support retrieving URLs.');
    }

    protected function getFtpUrl($path): string
    {
        return isset($this->config['url'])
            ? $this->concatPathToUrl($this->config['url'], $path)
            : $path;
    }

    protected function getLocalUrl($path): string
    {
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $path);
        }

        $path = '/storage/'.$path;

        if (str_contains($path, '/storage/public/')) {
            return Str::replaceFirst('/public/', '/', $path);
        }

        return $path;
    }

    public function providesTemporaryUrls(): bool
    {
        return method_exists($this->adapter, 'getTemporaryUrl') || isset($this->temporaryUrlCallback);
    }

    public function providesTemporaryUploadUrls(): bool
    {
        return method_exists($this->adapter, 'temporaryUploadUrl') || isset($this->temporaryUploadUrlCallback);
    }

    public function temporaryUrl($path, $expiration, array $options = [])
    {
        if (method_exists($this->adapter, 'getTemporaryUrl')) {
            return $this->adapter->getTemporaryUrl($path, $expiration, $options);
        }

        if ($this->temporaryUrlCallback) {
            return $this->temporaryUrlCallback->bindTo($this, static::class)($path, $expiration, $options);
        }

        throw new RuntimeException('This driver does not support creating temporary URLs.');
    }

    public function temporaryUploadUrl($path, $expiration, array $options = []): array
    {
        if (method_exists($this->adapter, 'temporaryUploadUrl')) {
            return $this->adapter->temporaryUploadUrl($path, $expiration, $options);
        }

        if ($this->temporaryUploadUrlCallback) {
            return $this->temporaryUploadUrlCallback->bindTo($this, static::class)($path, $expiration, $options);
        }

        throw new RuntimeException('This driver does not support creating temporary upload URLs.');
    }

    protected function concatPathToUrl($url, $path): string
    {
        return rtrim($url, '/').'/'.ltrim($path, '/');
    }

    protected function replaceBaseUrl(UriInterface $uri, string $url): UriInterface
    {
        $parsed = parse_url($url);

        return $uri
            ->withScheme($parsed['scheme'])
            ->withHost($parsed['host'])
            ->withPort($parsed['port'] ?? null);
    }

    public function files($directory = null, $recursive = false): array
    {
        return $this->driver->listContents($directory ?? '', $recursive)
            ->filter(fn (StorageAttributes $attributes) => $attributes->isFile())
            ->sortByPath()
            ->map(fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();
    }

    public function allFiles($directory = null): array
    {
        return $this->files($directory, true);
    }

    public function directories($directory = null, $recursive = false): array
    {
        return $this->driver->listContents($directory ?? '', $recursive)
            ->filter(fn (StorageAttributes $attributes) => $attributes->isDir())
            ->map(fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();
    }

    public function allDirectories($directory = null): array
    {
        return $this->directories($directory, true);
    }

    public function makeDirectory($path): bool
    {
        try {
            $this->driver->createDirectory($path);
        } catch (UnableToCreateDirectory|UnableToSetVisibility $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return false;
        }

        return true;
    }

    public function deleteDirectory($directory): bool
    {
        try {
            $this->driver->deleteDirectory($directory);
        } catch (UnableToDeleteDirectory $e) {
            if ($this->throwsExceptions()) {
                throw $e;
            }

            $this->report($e);

            return false;
        }

        return true;
    }

    public function getDriver(): FilesystemOperator
    {
        return $this->driver;
    }

    public function getAdapter(): FlysystemAdapter
    {
        return $this->adapter;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param  string|\Fabricate\Filesystem\Enums\VISIBILITY|null  $visibility
     */
    protected function parseVisibility($visibility): ?string
    {
        if (is_null($visibility)) {
            return null;
        }

        if ($visibility instanceof VISIBILITY) {
            $visibility = $visibility->value;
        }

        return match ($visibility) {
            VISIBILITY::PUBLIC->value => Visibility::PUBLIC,
            VISIBILITY::PRIVATE->value => Visibility::PRIVATE,
            default => throw new InvalidArgumentException("Unknown visibility: {$visibility}."),
        };
    }

    public function buildTemporaryUrlsUsing(Closure $callback): void
    {
        $this->temporaryUrlCallback = $callback;
    }

    public function buildTemporaryUploadUrlsUsing(Closure $callback): void
    {
        $this->temporaryUploadUrlCallback = $callback;
    }

    protected function throwsExceptions(): bool
    {
        return (bool) ($this->config['throw'] ?? false);
    }

    protected function shouldReport(): bool
    {
        return (bool) ($this->config['report'] ?? false);
    }

    protected function report(Throwable $exception): void
    {
        if (! $this->shouldReport()) {
            return;
        }

        $container = Chassis::getInstance();

        if (! $container->bound(ExceptionHandler::class)) {
            return;
        }

        $handler = $container->make(ExceptionHandler::class);
        $handler->report($exception);
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        try {
            return $this->driver->{$method}(...$parameters);
        } catch (BadMethodCallException $e) {
            throw $e;
        }
    }
}
