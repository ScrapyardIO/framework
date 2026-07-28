<?php

namespace DeptOfScrapyardRobotics\Tests\Sketches;

use DeptOfScrapyardRobotics\Tests\Sketches\Fixtures\AttributedPackageSketch;
use Fabricate\Config\Repository;
use Fabricate\Console\ConsoleProgram;
use Fabricate\Console\OutputStyle;
use Fabricate\Core\Console\SketchMakeCommand;
use Fabricate\Core\Machine;
use Fabricate\Core\Providers\WorkshopServiceProvider;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Filesystem\Filesystem;
use Fabricate\Sketches\Console\SketchCommand;
use Fabricate\Sketches\Console\SketchListCommand;
use Fabricate\Sketches\Sketch;
use Fabricate\Sketches\SketchRegistry;
use Fabricate\Sketches\SketchRunner;
use Fabricate\Sketches\SketchesServiceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class SketchCommandsTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        ConsoleProgram::forgetBootstrappers();
        $this->basePath = sys_get_temp_dir().'/scrapyard-sketch-cmd-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem())->mkdir([
            $this->basePath.'/app/Sketches',
            $this->basePath.'/bootstrap/cache',
            $this->basePath.'/config',
            $this->basePath.'/storage',
        ]);
        $this->basePath = realpath($this->basePath) ?: $this->basePath;

        file_put_contents($this->basePath.'/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        ConsoleProgram::forgetBootstrappers();
        Machine::setInstance(null);
        (new SymfonyFilesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testMakeSketchGeneratesAppSketchSubclass(): void
    {
        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository([]));
        $machine->instance('files', new Filesystem);
        $machine->register(WorkshopServiceProvider::class);

        $program = (new ConsoleProgram($machine, $machine->make('events'), '0.6.0'))
            ->setContainerCommandLoader();

        $status = $program->call('make:sketch', ['name' => 'PulseMonitor']);

        $path = $this->basePath.'/app/Sketches/PulseMonitor.php';

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertStringContainsString('namespace App\\Sketches;', $contents);
        $this->assertStringContainsString('class PulseMonitor extends Sketch', $contents);
        $this->assertStringContainsString('use Fabricate\\Contracts\\Sketches\\SketchLoopResult;', $contents);
        $this->assertStringContainsString('protected string $description = \'\';', $contents);
        $this->assertStringContainsString('public function boot(): void', $contents);
        $this->assertStringContainsString('public function loop(): SketchLoopResult', $contents);
        $this->assertStringContainsString('public function shutdown(): void', $contents);
        $this->assertInstanceOf(SketchMakeCommand::class, $machine->make(SketchMakeCommand::class));
    }

    public function testWorkshopSketchRunsResolvedSketch(): void
    {
        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository([
            'sketches' => ['load' => []],
        ]));
        $machine->register(SketchesServiceProvider::class);
        $machine->boot();

        /** @var SketchRegistry $registry */
        $registry = $machine->make(SketchRegistry::class);
        $registry->register(AttributedPackageSketch::class);

        // Provider register() queues the command onto ConsoleProgram::starting bootstrappers.
        $program = (new ConsoleProgram($machine, $machine->make('events'), '0.6.0'))
            ->setContainerCommandLoader();

        $this->assertTrue($program->has('sketch'));

        $status = $program->call('sketch', ['name' => 'package-blink']);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertInstanceOf(SketchCommand::class, $machine->make(SketchCommand::class));
        $this->assertInstanceOf(SketchRunner::class, $machine->make(SketchRunner::class));
    }

    public function testSketchListShowsRegisteredSketchesWithDescriptions(): void
    {
        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository([
            'sketches' => ['load' => []],
        ]));
        $machine->register(SketchesServiceProvider::class);
        $machine->boot();

        /** @var SketchRegistry $registry */
        $registry = $machine->make(SketchRegistry::class);
        $registry->register(AttributedPackageSketch::class);

        $program = (new ConsoleProgram($machine, $machine->make('events'), '0.6.0'))
            ->setContainerCommandLoader();

        $this->assertTrue($program->has('sketch:list'));

        $status = $program->call('sketch:list');

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertInstanceOf(SketchListCommand::class, $machine->make(SketchListCommand::class));

        $display = $program->output();
        $this->assertStringContainsString('package-blink', $display);
        $this->assertStringContainsString('Attributed package blink sketch.', $display);
        $this->assertStringContainsString('Name', $display);
        $this->assertStringContainsString('Description', $display);
    }

    public function testSketchListReportsEmptyRegistry(): void
    {
        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository([
            'sketches' => ['load' => []],
        ]));
        $machine->register(SketchesServiceProvider::class);
        $machine->boot();

        $program = (new ConsoleProgram($machine, $machine->make('events'), '0.6.0'))
            ->setContainerCommandLoader();

        $status = $program->call('sketch:list');

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('No sketches have been registered.', $program->output());
    }

    public function testSketchDescriptionAndConfigureIOHelpers(): void
    {
        $sketch = new class extends Sketch
        {
            protected string $description = 'IO probe sketch.';

            public function loop(): SketchLoopResult
            {
                return SketchLoopResult::STOP;
            }
        };

        $this->assertSame('IO probe sketch.', $sketch->getDescription());

        $input = new ArrayInput([]);
        $buffer = new BufferedOutput;
        $output = new OutputStyle($input, $buffer);

        $sketch->configureIO($input, $output);
        $sketch->info('info-line');
        $sketch->warn('warn-line');
        $sketch->error('error-line');

        $display = $buffer->fetch();

        $this->assertStringContainsString('info-line', $display);
        $this->assertStringContainsString('warn-line', $display);
        $this->assertStringContainsString('error-line', $display);
    }
}
