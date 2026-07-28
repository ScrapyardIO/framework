<?php

namespace DeptOfScrapyardRobotics\Tests\Filesystem;

use Fabricate\Config\Repository;
use Fabricate\Contracts\Filesystem\FilesystemFactory;
use Fabricate\Core\Machine;
use Fabricate\Filesystem\Filesystem;
use Fabricate\Filesystem\FilesystemManager;
use Fabricate\Filesystem\FilesystemServiceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class FilesystemServiceProviderTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/scrapyard-fs-provider-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem())->mkdir([
            $this->basePath.'/bootstrap/cache',
            $this->basePath.'/config',
            $this->basePath.'/storage/app',
        ]);
    }

    protected function tearDown(): void
    {
        (new SymfonyFilesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testProviderRegistersFilesystemBindings(): void
    {
        $app = new Machine($this->basePath);
        $app->instance('config', new Repository([
            'filesystems' => [
                'default' => 'local',
                'cloud' => 'local',
                'disks' => [
                    'local' => [
                        'driver' => 'local',
                        'root' => $this->basePath.'/storage/app',
                    ],
                ],
            ],
        ]));

        $provider = new FilesystemServiceProvider($app);
        $provider->register();

        $this->assertTrue($app->bound('files'));
        $this->assertTrue($app->bound('filesystem'));
        $this->assertTrue($app->bound('filesystem.disk'));
        $this->assertTrue($app->bound('filesystem.cloud'));

        $this->assertInstanceOf(Filesystem::class, $app->make('files'));
        $this->assertInstanceOf(FilesystemManager::class, $app->make('filesystem'));
        $this->assertInstanceOf(FilesystemFactory::class, $app->make('filesystem'));
    }
}
