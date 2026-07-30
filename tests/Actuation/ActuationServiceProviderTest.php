<?php

namespace DeptOfScrapyardRobotics\Tests\Actuation;

use Fabricate\Actuation\ActuationServiceProvider;
use Fabricate\Actuation\ActuatorRegistry;
use Fabricate\Config\Repository;
use Fabricate\Contracts\Actuation\ActuatorRegistry as ActuatorRegistryContract;
use Fabricate\Core\Machine;
use Fabricate\NutsAndBolts\MagicAliases\MagicAlias;
use Fabricate\NutsAndBolts\ServiceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ActuationServiceProviderTest extends TestCase
{
    private string $base_path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->base_path = sys_get_temp_dir().'/fabricate-actuation-'.bin2hex(random_bytes(8));
        (new Filesystem)->mkdir([$this->base_path.'/bootstrap/cache', $this->base_path.'/config']);
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];
    }

    protected function tearDown(): void
    {
        Machine::setInstance(null);
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];
        (new Filesystem)->remove($this->base_path);

        parent::tearDown();
    }

    public function testProviderMergesPublishesAndAliasesActuationServices(): void
    {
        $machine = new Machine($this->base_path);
        $machine->instance('config', new Repository([
            'actuators' => ['fans' => ['circuits' => ['custom' => ['driver' => 'custom']]]],
        ]));
        $provider = $machine->register(ActuationServiceProvider::class);
        $provider->boot();

        $registry = $machine->make(ActuatorRegistryContract::class);

        $this->assertInstanceOf(ActuatorRegistry::class, $registry);
        $this->assertSame($registry, $machine->make('actuator'));
        $this->assertSame(
            ['driver' => 'custom'],
            $machine->make('config')->get('actuators.fans.circuits.custom'),
        );
        $this->assertContains(
            $this->base_path.'/config/actuators.php',
            ServiceProvider::pathsToPublish(ActuationServiceProvider::class, 'actuators-config'),
        );
        $this->assertContains(ActuationServiceProvider::class, ServiceProvider::defaultProviders()->toArray());
        $this->assertTrue(MagicAlias::defaultAliases()->has('Actuator'));
    }
}
