<?php

namespace DeptOfScrapyardRobotics\Tests\Core;

use Fabricate\Config\Repository;
use Fabricate\Contracts\Core\VisualException;
use Fabricate\Core\Machine;
use Fabricate\Core\PendingVisualPresentation;
use Fabricate\Core\VisualManager;
use Fabricate\Core\VisualPresentation;
use PHPUnit\Framework\TestCase;

class VisualManagerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/scrapyard-visual-mgr-'.bin2hex(random_bytes(8));
        mkdir($this->basePath);
        $this->basePath = realpath($this->basePath) ?: $this->basePath;
    }

    protected function tearDown(): void
    {
        Machine::setInstance(null);

        if (is_dir($this->basePath)) {
            rmdir($this->basePath);
        }

        parent::tearDown();
    }

    public function testMainReturnsNullForConsole(): void
    {
        $this->bindConfig([
            'displays' => [
                'main' => ['type' => 'console'],
            ],
        ]);

        $manager = $this->getMockBuilder(VisualManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['display'])
            ->getMock();

        $manager->expects($this->never())->method('display');

        $this->assertNull($manager->main());
    }

    public function testMainBuildsWindowedPresentationFromConfig(): void
    {
        $this->bindConfig([
            'displays' => [
                'main' => [
                    'type' => 'windowed',
                    'driver' => 'glfw',
                    'renderer' => 'glfw',
                    'buffer' => 'glfw-ogl',
                ],
            ],
        ]);

        $presentation = $this->createStub(VisualPresentation::class);
        $pending = $this->createMock(PendingVisualPresentation::class);
        $pending->expects($this->once())->method('renderer')->with('glfw')->willReturnSelf();
        $pending->expects($this->once())->method('buffer')->with('glfw-ogl')->willReturnSelf();
        $pending->expects($this->once())->method('present')->willReturn($presentation);

        $manager = $this->getMockBuilder(VisualManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['display'])
            ->getMock();

        $manager->expects($this->once())
            ->method('display')
            ->with('windowed', 'glfw')
            ->willReturn($pending);

        $this->assertSame($presentation, $manager->main());
    }

    public function testMainBuildsEmbeddedPresentationFromConfig(): void
    {
        $this->bindConfig([
            'displays' => [
                'main' => [
                    'type' => 'embedded',
                    'driver' => 'monochrome',
                    'circuit' => 'ssd1306',
                    'renderer' => 'phpdafruit',
                    'buffer' => 'page',
                ],
            ],
        ]);

        $presentation = $this->createStub(VisualPresentation::class);
        $pending = $this->createMock(PendingVisualPresentation::class);
        $pending->expects($this->once())->method('renderer')->with('phpdafruit')->willReturnSelf();
        $pending->expects($this->once())->method('buffer')->with('page')->willReturnSelf();
        $pending->expects($this->once())->method('present')->willReturn($presentation);

        $manager = $this->getMockBuilder(VisualManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['display'])
            ->getMock();

        $manager->expects($this->once())
            ->method('display')
            ->with('embedded', 'monochrome', 'ssd1306')
            ->willReturn($pending);

        $this->assertSame($presentation, $manager->main());
    }

    public function testMainRejectsMissingRenderer(): void
    {
        $this->bindConfig([
            'displays' => [
                'main' => [
                    'type' => 'windowed',
                    'driver' => 'glfw',
                    'buffer' => 'glfw-ogl',
                ],
            ],
        ]);

        $manager = $this->getMockBuilder(VisualManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['display'])
            ->getMock();

        $this->expectException(VisualException::class);
        $this->expectExceptionMessage('missing renderer');

        $manager->main();
    }

    public function testMainRejectsEmbeddedWithoutCircuit(): void
    {
        $this->bindConfig([
            'displays' => [
                'main' => [
                    'type' => 'embedded',
                    'driver' => 'monochrome',
                    'renderer' => 'phpdafruit',
                    'buffer' => 'page',
                ],
            ],
        ]);

        $manager = $this->getMockBuilder(VisualManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['display'])
            ->getMock();

        $this->expectException(VisualException::class);
        $this->expectExceptionMessage('embedded mains require circuit');

        $manager->main();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function bindConfig(array $config): void
    {
        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository($config));
    }
}
