<?php

namespace DeptOfScrapyardRobotics\Tests\Log;

use Fabricate\Config\Repository;
use Fabricate\Core\Machine;
use Fabricate\Log\LogManager;
use Fabricate\Log\Logger;
use Monolog\Handler\NullHandler;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class LogManagerTest extends TestCase
{
    private string $basePath;

    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/scrapyard-log-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem())->mkdir($this->basePath.'/storage/logs');
        $this->machine = new Machine($this->basePath);
        $this->machine->instance('config', new Repository([
            'logging' => [
                'default' => 'single',
                'channels' => [
                    'single' => [
                        'driver' => 'single',
                        'path' => $this->basePath.'/storage/logs/framework.log',
                        'level' => 'debug',
                    ],
                    'null' => [
                        'driver' => 'null',
                    ],
                ],
            ],
        ]));
    }

    protected function tearDown(): void
    {
        (new SymfonyFilesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testItBuildsTheDefaultChannelAndWritesToItsConfiguredPath(): void
    {
        $manager = new LogManager($this->machine);

        $manager->info('Machine started', ['machine' => 'scrapyard']);

        $contents = file_get_contents($this->basePath.'/storage/logs/framework.log');
        $this->assertStringContainsString('Machine started', $contents);
        $this->assertStringContainsString('"machine":"scrapyard"', $contents);
        $this->assertSame('single', $manager->getDefaultDriver());
    }

    public function testResolvedChannelsAreCachedAndCanBeForgotten(): void
    {
        $manager = new LogManager($this->machine);

        $first = $manager->channel('null');
        $second = $manager->channel('null');

        $this->assertSame($first, $second);
        $this->assertArrayHasKey('null', $manager->getChannels());

        $manager->forgetChannel('null');

        $this->assertArrayNotHasKey('null', $manager->getChannels());
        $this->assertNotSame($first, $manager->channel('null'));
    }

    public function testSharedContextPropagatesToExistingAndFutureChannels(): void
    {
        $manager = new LogManager($this->machine);
        $single = $manager->channel('single');
        $manager->shareContext(['machine' => 'scrapyard']);
        $null = $manager->channel('null');

        $this->assertSame(['machine' => 'scrapyard'], $manager->sharedContext());
        $this->assertInstanceOf(Logger::class, $single);
        $this->assertInstanceOf(Logger::class, $null);

        $manager->withoutContext(['machine']);

        $this->assertSame([], $manager->sharedContext());
    }

    public function testCustomDriversCanBeRegistered(): void
    {
        $manager = new LogManager($this->machine);
        $manager->extend('memory', fn () => new Monolog('memory', [new NullHandler()]));

        $logger = $manager->build(['driver' => 'memory']);

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame('memory', $logger->getLogger()->getName());
    }
}
