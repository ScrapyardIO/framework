<?php

namespace DeptOfScrapyardRobotics\Tests\Core;

use Fabricate\Config\Repository;
use Fabricate\Core\Bootstrap\LoadConfiguration;
use Fabricate\Core\Machine;
use Fabricate\Core\PackageManifest;
use Fabricate\Events\Dispatcher;
use Fabricate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class MachineTest extends TestCase
{
    private string $basePath;

    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTimezone = date_default_timezone_get();
        $this->basePath = sys_get_temp_dir().'/scrapyard-machine-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem())->mkdir([
            $this->basePath.'/app',
            $this->basePath.'/bootstrap/cache',
            $this->basePath.'/config',
            $this->basePath.'/storage',
        ]);
    }

    protected function tearDown(): void
    {
        LoadConfiguration::alwaysUse(null);
        date_default_timezone_set($this->originalTimezone);
        unset($_ENV['SCRAPYARD_IOSTORAGE_PATH'], $_SERVER['SCRAPYARD_IOSTORAGE_PATH']);
        (new SymfonyFilesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testItRegistersItsFoundationalBindings(): void
    {
        $machine = new Machine($this->basePath);

        $this->assertSame($machine, $machine->make('app'));
        $this->assertInstanceOf(Dispatcher::class, $machine->make('events'));
        $this->assertInstanceOf(Filesystem::class, $machine->make('files'));
        $this->assertInstanceOf(PackageManifest::class, $machine->make(PackageManifest::class));
    }

    public function testItsApplicationPathsAreDerivedFromTheBasePath(): void
    {
        $machine = new Machine($this->basePath);

        $this->assertSame($this->basePath, $machine->basePath());
        $this->assertSame($this->basePath.'/app/Console', $machine->path('Console'));
        $this->assertSame($this->basePath.'/bootstrap/cache', $machine->bootstrapPath('cache'));
        $this->assertSame($this->basePath.'/config/machine.php', $machine->configPath('machine.php'));
        $this->assertSame($this->basePath.'/storage/logs', $machine->storagePath('logs'));

        $_ENV['SCRAPYARD_IOSTORAGE_PATH'] = $this->basePath.'/custom-storage';

        $this->assertSame($this->basePath.'/custom-storage/logs', $machine->storagePath('logs'));
    }

    public function testBootAndTerminationCallbacksRunInRegistrationOrder(): void
    {
        $machine = new Machine($this->basePath);
        $calls = [];
        $machine->booting(function () use (&$calls): void {
            $calls[] = 'booting';
        });
        $machine->booted(function () use (&$calls): void {
            $calls[] = 'booted';
        });
        $machine->terminating(function () use (&$calls): void {
            $calls[] = 'terminating';
        });

        $machine->boot();
        $machine->boot();
        $machine->terminate();

        $this->assertSame(['booting', 'booted', 'terminating'], $calls);
        $this->assertTrue($machine->isBooted());
    }

    public function testBootstrapDispatchesLifecycleEventsAroundEachBootstrapper(): void
    {
        RecordingBootstrapper::$calls = [];
        $machine = new Machine($this->basePath);
        $events = [];
        $machine->make('events')->listen('bootstrapping: '.RecordingBootstrapper::class, function () use (&$events): void {
            $events[] = 'before';
        });
        $machine->make('events')->listen('bootstrapped: '.RecordingBootstrapper::class, function () use (&$events): void {
            $events[] = 'after';
        });

        $machine->bootstrapWith([RecordingBootstrapper::class]);

        $this->assertSame(['before', 'after'], $events);
        $this->assertSame([$machine], RecordingBootstrapper::$calls);
        $this->assertTrue($machine->hasBeenBootstrapped());
    }

    public function testConfigurationBootstrapLoadsApplicationFilesAndEnvironment(): void
    {
        file_put_contents(
            $this->basePath.'/config/machine.php',
            "<?php return ['env' => 'testing', 'timezone' => 'America/New_York', 'name' => 'Scrapyard'];",
        );
        file_put_contents(
            $this->basePath.'/config/logging.php',
            "<?php return ['default' => 'stderr', 'channels' => ['stderr' => ['driver' => 'errorlog']]];",
        );
        $machine = new Machine($this->basePath);

        (new LoadConfiguration())->bootstrap($machine);

        $this->assertInstanceOf(Repository::class, $machine->make('config'));
        $this->assertSame('Scrapyard', $machine->make('config')->get('machine.name'));
        $this->assertSame('stderr', $machine->make('config')->get('logging.default'));
        $this->assertSame('testing', $machine->environment());
        $this->assertTrue($machine->runningUnitTests());
        $this->assertSame('America/New_York', date_default_timezone_get());
        $this->assertFalse($machine->make('config_loaded_from_cache'));
    }
}

class RecordingBootstrapper
{
    public static array $calls = [];

    public function bootstrap(Machine $machine): void
    {
        self::$calls[] = $machine;
    }
}
