<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Config\Repository;
use Fabricate\Console\ConsoleProgram;
use Fabricate\Core\Console\NodeMakeCommand;
use Fabricate\Core\Machine;
use Fabricate\Core\Providers\WorkshopServiceProvider;
use Fabricate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class NodeMakeCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        ConsoleProgram::forgetBootstrappers();
        $this->basePath = sys_get_temp_dir().'/scrapyard-node-make-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem)->mkdir([
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
        (new SymfonyFilesystem)->remove($this->basePath);

        parent::tearDown();
    }

    public function testMakeNodeGeneratesANodeSubclassWithTheThreeMethodsThatMatter(): void
    {
        $program = $this->program();

        $status = $program->call('make:node', ['name' => 'BatteryMeter']);
        $path = $this->basePath.'/app/Nodes/BatteryMeter.php';

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertFileExists($path);

        $contents = file_get_contents($path);

        $this->assertStringContainsString('namespace App\\Nodes;', $contents);
        $this->assertStringContainsString('class BatteryMeter extends Node', $contents);

        // The three a node author has to think about: how big it wants to be,
        // what it draws, and whether it covers its own box — the last being the
        // claim the stage relies on to erase.
        $this->assertStringContainsString('public function measure(Constraints $constraints): Size', $contents);
        $this->assertStringContainsString('public function paint(DrawingSurface $surface): void', $contents);
        $this->assertStringContainsString('public function isOpaque(): bool', $contents);
        $this->assertStringContainsString('return false;', $contents);
    }

    public function testMakeNodeAcceptsANestedName(): void
    {
        $this->program()->call('make:node', ['name' => 'Dashboard/BatteryMeter']);

        $contents = file_get_contents($this->basePath.'/app/Nodes/Dashboard/BatteryMeter.php');

        $this->assertStringContainsString('namespace App\\Nodes\\Dashboard;', $contents);
        $this->assertStringContainsString('class BatteryMeter extends Node', $contents);
    }

    public function testMakeNodeForceOverwritesAnExistingNode(): void
    {
        $program = $this->program();
        $path = $this->basePath.'/app/Nodes/BatteryMeter.php';

        $program->call('make:node', ['name' => 'BatteryMeter']);
        file_put_contents($path, '<?php // existing node');

        $status = $program->call('make:node', [
            'name' => 'BatteryMeter',
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('class BatteryMeter extends Node', file_get_contents($path));
    }

    public function testTheCommandIsRegisteredWithTheWorkshop(): void
    {
        $machine = $this->machine();

        $this->assertInstanceOf(NodeMakeCommand::class, $machine->make(NodeMakeCommand::class));
    }

    protected function machine(): Machine
    {
        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository([]));
        $machine->instance('files', new Filesystem);
        $machine->register(WorkshopServiceProvider::class);

        return $machine;
    }

    protected function program(): ConsoleProgram
    {
        $machine = $this->machine();

        return (new ConsoleProgram($machine, $machine->make('events'), '0.6.0'))
            ->setContainerCommandLoader();
    }
}
