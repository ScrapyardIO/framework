<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\Filesystem\Filesystem;
use Fabricate\MagicAliases\MagicAlias;
use UnitEnum;

use function Fabricate\NutsAndBolts\Helpers\enum_value;

/**
 * @method static \Fabricate\Contracts\Filesystem\Filesystem drive(\UnitEnum|string|null $name = null)
 * @method static \Fabricate\Contracts\Filesystem\Filesystem disk(\UnitEnum|string|null $name = null)
 * @method static \Fabricate\Contracts\Filesystem\Cloud cloud()
 * @method static \Fabricate\Contracts\Filesystem\Filesystem build(string|array $config)
 * @method static \Fabricate\Contracts\Filesystem\Filesystem createLocalDriver(array $config, string $name = 'local')
 * @method static \Fabricate\Contracts\Filesystem\Filesystem createFtpDriver(array $config)
 * @method static \Fabricate\Contracts\Filesystem\Filesystem createSftpDriver(array $config)
 * @method static \Fabricate\Contracts\Filesystem\Cloud createS3Driver(array $config)
 * @method static \Fabricate\Contracts\Filesystem\Filesystem createScopedDriver(array $config)
 * @method static \Fabricate\Filesystem\FilesystemManager set(string $name, mixed $disk)
 * @method static string getDefaultDriver()
 * @method static string getDefaultCloudDriver()
 * @method static \Fabricate\Filesystem\FilesystemManager forgetDisk(array|string $disk)
 * @method static void purge(string|null $name = null)
 * @method static \Fabricate\Filesystem\FilesystemManager extend(string $driver, \Closure $callback)
 * @method static \Fabricate\Filesystem\FilesystemManager setContainer(\Fabricate\Contracts\Chassis\ServiceContainer $container)
 *
 * @see \Fabricate\Filesystem\FilesystemManager
 */
class Storage extends MagicAlias
{
    /**
     * Replace the given disk with a local testing disk.
     *
     * @param  UnitEnum|string|null  $disk
     * @param  array  $config
     * @return \Fabricate\Filesystem\LocalFilesystemAdapter
     */
    public static function fake($disk = null, array $config = [])
    {
        $root = self::getRootPath($disk = enum_value($disk) ?: static::$container['config']->get('filesystems.default'));

        (new Filesystem)->cleanDirectory($root);

        static::set($disk, $fake = static::createLocalDriver(
            self::buildDiskConfiguration($disk, $config, root: $root)
        ));

        return tap($fake, function ($fake) {
            $fake->buildTemporaryUrlsUsing(function ($path, $expiration) {
                return $path.'?expiration='.$expiration->getTimestamp();
            });

            $fake->buildTemporaryUploadUrlsUsing(function ($path, $expiration) {
                return ['url' => $path.'?expiration='.$expiration->getTimestamp(), 'headers' => []];
            });
        });
    }

    /**
     * Replace the given disk with a persistent local testing disk.
     *
     * @param  UnitEnum|string|null  $disk
     * @param  array  $config
     * @return \Fabricate\Filesystem\LocalFilesystemAdapter
     */
    public static function persistentFake($disk = null, array $config = [])
    {
        $disk = enum_value($disk) ?: static::$container['config']->get('filesystems.default');

        static::set($disk, $fake = static::createLocalDriver(
            self::buildDiskConfiguration($disk, $config, root: self::getRootPath($disk))
        ));

        return $fake;
    }

    /**
     * Get the root path of the given disk.
     *
     * @param  string  $disk
     * @return string
     */
    protected static function getRootPath(string $disk): string
    {
        return storage_path('framework/testing/disks/'.$disk);
    }

    /**
     * Assemble the configuration of the given disk.
     *
     * @param  string  $disk
     * @param  array  $config
     * @param  string  $root
     * @return array
     */
    protected static function buildDiskConfiguration(string $disk, array $config, string $root): array
    {
        $originalConfig = static::$container['config']["filesystems.disks.{$disk}"] ?? [];

        return array_merge([
            'throw' => $originalConfig['throw'] ?? false],
            $config,
            ['root' => $root]
        );
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'filesystem';
    }
}
