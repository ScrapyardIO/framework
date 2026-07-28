<?php

namespace DeptOfScrapyardRobotics\Tests\Filesystem;

use Fabricate\Contracts\Filesystem\Filesystem;
use Fabricate\Core\Machine;
use Fabricate\Filesystem\FilesystemManager;
use InvalidArgumentException;
use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class FilesystemManagerTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = sys_get_temp_dir().'/scrapyard-fs-manager-'.bin2hex(random_bytes(6));
        (new SymfonyFilesystem())->mkdir($this->tempRoot);
    }

    protected function tearDown(): void
    {
        (new SymfonyFilesystem())->remove($this->tempRoot);

        parent::tearDown();
    }

    public function testExceptionThrownOnUnsupportedDriver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Disk [local] does not have a configured driver.');

        $filesystem = new FilesystemManager(tap(new Machine, function ($app) {
            $app['config'] = ['filesystems.disks.local' => null];
        }));

        $filesystem->disk('local');
    }

    public function testCanBuildOnDemandDisk()
    {
        $filesystem = new FilesystemManager(new Machine);
        $path = $this->tempRoot.'/on-demand';

        $this->assertInstanceOf(Filesystem::class, $filesystem->build($path));

        $this->assertInstanceOf(Filesystem::class, $filesystem->build([
            'driver' => 'local',
            'root' => $path,
            'url' => 'my-custom-url',
            'visibility' => 'public',
        ]));
    }

    public function testCanBuildReadOnlyDisks()
    {
        $filesystem = new FilesystemManager(new Machine);
        $path = $this->tempRoot.'/read-only';
        mkdir($path);

        $disk = $filesystem->build([
            'driver' => 'local',
            'read-only' => true,
            'root' => $path,
            'url' => 'my-custom-url',
            'visibility' => 'public',
        ]);

        file_put_contents($path.'/path.txt', 'contents');

        $this->assertEquals('contents', $disk->get('path.txt'));
        $this->assertEquals(['path.txt'], $disk->files());

        $this->assertFalse($disk->put('path.txt', 'contents'));
        $this->assertFalse($disk->delete('path.txt'));
        $this->assertFalse($disk->deleteDirectory('directory'));
        $this->assertFalse($disk->prepend('path.txt', 'data'));
        $this->assertFalse($disk->append('path.txt', 'data'));
        $handle = fopen('php://memory', 'rw');
        fwrite($handle, 'content');
        $this->assertFalse($disk->writeStream('path.txt', $handle));
        fclose($handle);
    }

    public function testCanBuildScopedDisks()
    {
        $root = $this->tempRoot.'/to-be-scoped';
        mkdir($root);

        $filesystem = new FilesystemManager(tap(new Machine, function ($app) use ($root) {
            $app['config'] = [
                'filesystems.disks.local' => [
                    'driver' => 'local',
                    'root' => $root,
                ],
            ];
        }));

        $local = $filesystem->disk('local');
        $scoped = $filesystem->build([
            'driver' => 'scoped',
            'disk' => 'local',
            'prefix' => 'path-prefix',
        ]);

        $scoped->put('dirname/filename.txt', 'file content');
        $this->assertEquals('file content', $local->get('path-prefix/dirname/filename.txt'));
        $local->deleteDirectory('path-prefix');
    }

    public function testCanBuildScopedDiskFromScopedDisk()
    {
        $root = $this->tempRoot.'/root-to-be-scoped';
        mkdir($root);

        $filesystem = new FilesystemManager(tap(new Machine, function ($app) use ($root) {
            $app['config'] = [
                'filesystems.disks.local' => [
                    'driver' => 'local',
                    'root' => $root,
                ],
                'filesystems.disks.scoped-from-root' => [
                    'driver' => 'scoped',
                    'disk' => 'local',
                    'prefix' => 'scoped-from-root-prefix',
                ],
            ];
        }));

        $disk = $filesystem->disk('local');
        $nestedScoped = $filesystem->build([
            'driver' => 'scoped',
            'disk' => 'scoped-from-root',
            'prefix' => 'nested-scoped-prefix',
        ]);

        $nestedScoped->put('dirname/filename.txt', 'file content');
        $this->assertEquals(
            'file content',
            $disk->get('scoped-from-root-prefix/nested-scoped-prefix/dirname/filename.txt')
        );
        $disk->deleteDirectory('scoped-from-root-prefix');
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testCanBuildScopedDisksWithVisibility()
    {
        $root = $this->tempRoot.'/to-be-scoped-visibility';
        mkdir($root);

        $filesystem = new FilesystemManager(tap(new Machine, function ($app) use ($root) {
            $app['config'] = [
                'filesystems.disks.local' => [
                    'driver' => 'local',
                    'root' => $root,
                    'visibility' => 'public',
                ],
            ];
        }));

        $scoped = $filesystem->build([
            'driver' => 'scoped',
            'disk' => 'local',
            'prefix' => 'path-prefix',
            'visibility' => 'private',
        ]);

        $scoped->put('dirname/filename.txt', 'file content');

        $this->assertEquals('private', $scoped->getVisibility('dirname/filename.txt'));
    }

    public function testCanBuildScopedDisksWithThrow()
    {
        $root = $this->tempRoot.'/to-be-scoped-throw';
        mkdir($root);

        $filesystem = new FilesystemManager(tap(new Machine, function ($app) use ($root) {
            $app['config'] = [
                'filesystems.disks.local' => [
                    'driver' => 'local',
                    'root' => $root,
                    'throw' => false,
                ],
            ];
        }));

        $scoped = $filesystem->build([
            'driver' => 'scoped',
            'disk' => 'local',
            'prefix' => 'path-prefix',
            'throw' => true,
        ]);

        $this->expectException(UnableToReadFile::class);
        $scoped->get('dirname/filename.txt');
    }

    public function testCanBuildInlineScopedDisks()
    {
        $root = $this->tempRoot.'/to-be-scoped-inline';

        $filesystem = new FilesystemManager(new Machine);

        $scoped = $filesystem->build([
            'driver' => 'scoped',
            'disk' => [
                'driver' => 'local',
                'root' => $root,
            ],
            'prefix' => 'path-prefix',
        ]);

        $scoped->put('dirname/filename.txt', 'file content');
        $this->assertTrue(is_dir($root.'/path-prefix'));
        $this->assertEquals(
            'file content',
            file_get_contents($root.'/path-prefix/dirname/filename.txt')
        );
    }
}
