<?php

namespace DeptOfScrapyardRobotics\Tests\Sensors;

use Fabricate\Config\Repository;
use Fabricate\Console\ConsoleProgram;
use Fabricate\Core\Console\SensorMakeCommand;
use Fabricate\Core\Machine;
use Fabricate\Core\Providers\WorkshopServiceProvider;
use Fabricate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class SensorMakeCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        ConsoleProgram::forgetBootstrappers();
        $this->basePath = sys_get_temp_dir().'/scrapyard-sensor-make-'.bin2hex(random_bytes(8));
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

    public function testMakeSensorGeneratesAppSensorSubclass(): void
    {
        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository([]));
        $machine->instance('files', new Filesystem);
        $machine->register(WorkshopServiceProvider::class);

        $program = (new ConsoleProgram($machine, $machine->make('events'), '0.6.0'))
            ->setContainerCommandLoader();

        $status = $program->call('make:sensor', ['name' => 'ThermalProbe']);
        $path = $this->basePath.'/app/Sensors/ThermalProbe.php';

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertStringContainsString('namespace App\\Sensors;', $contents);
        $this->assertStringContainsString('class ThermalProbe extends Sensor', $contents);
        $this->assertStringContainsString('public function __construct(IntegratedCircuit $circuit)', $contents);
        $this->assertStringContainsString('public static function circuit(string $driver): static', $contents);
        $this->assertStringContainsString('$circuit = Circuit::driver($driver);', $contents);
        $this->assertInstanceOf(SensorMakeCommand::class, $machine->make(SensorMakeCommand::class));
    }

    public function testMakeSensorForceOverwritesExistingSensor(): void
    {
        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository([]));
        $machine->instance('files', new Filesystem);
        $machine->register(WorkshopServiceProvider::class);

        $program = (new ConsoleProgram($machine, $machine->make('events'), '0.6.0'))
            ->setContainerCommandLoader();
        $path = $this->basePath.'/app/Sensors/ThermalProbe.php';

        $program->call('make:sensor', ['name' => 'ThermalProbe']);
        file_put_contents($path, '<?php // existing sensor');

        $status = $program->call('make:sensor', [
            'name' => 'ThermalProbe',
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString(
            'class ThermalProbe extends Sensor',
            file_get_contents($path),
        );
    }
}
