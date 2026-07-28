<?php

namespace DeptOfScrapyardRobotics\Tests\Chassis;

use Fabricate\Chassis\Chassis;
use Fabricate\Chassis\EntryNotFoundException;
use PHPUnit\Framework\TestCase;

class ChassisTest extends TestCase
{
    public function testTransientSingletonAndInstanceBindingsResolveCorrectly(): void
    {
        $container = new Chassis();
        $container->bind(Engine::class, ElectricEngine::class);

        $this->assertInstanceOf(ElectricEngine::class, $container->make(Engine::class));
        $this->assertNotSame($container->make(Engine::class), $container->make(Engine::class));

        $container->singleton(SharedService::class);

        $this->assertSame($container->make(SharedService::class), $container->make(SharedService::class));

        $instance = new SharedService();
        $container->instance('existing', $instance);

        $this->assertSame($instance, $container->make('existing'));
    }

    public function testConstructorDependenciesAndDefaultsAreInjected(): void
    {
        $container = new Chassis();
        $container->bind(Engine::class, ElectricEngine::class);

        $vehicle = $container->make(Vehicle::class);

        $this->assertInstanceOf(ElectricEngine::class, $vehicle->engine);
        $this->assertSame('scrapyard', $vehicle->name);
    }

    public function testContextualBindingsCanVaryAnImplementationByConsumer(): void
    {
        $container = new Chassis();
        $container->bind(Engine::class, ElectricEngine::class);
        $container->when(Generator::class)
            ->needs(Engine::class)
            ->give(CombustionEngine::class);

        $this->assertInstanceOf(ElectricEngine::class, $container->make(Vehicle::class)->engine);
        $this->assertInstanceOf(CombustionEngine::class, $container->make(Generator::class)->engine);
    }

    public function testTaggedBindingsResolveLazily(): void
    {
        $container = new Chassis();
        $container->bind('first.engine', ElectricEngine::class);
        $container->bind('second.engine', CombustionEngine::class);
        $container->tag(['first.engine', 'second.engine'], 'engines');

        $engines = $container->tagged('engines');

        $this->assertCount(2, $engines);
        $this->assertInstanceOf(ElectricEngine::class, [...$engines][0]);
        $this->assertInstanceOf(CombustionEngine::class, [...$engines][1]);
    }

    public function testCallInjectsDependenciesAndHonorsNamedOverrides(): void
    {
        $container = new Chassis();

        $result = $container->call(
            fn (ElectricEngine $engine, string $status = 'idle') => [$engine, $status],
            ['status' => 'running'],
        );

        $this->assertInstanceOf(ElectricEngine::class, $result[0]);
        $this->assertSame('running', $result[1]);
    }

    public function testAliasesAndExtendersDecorateResolvedServices(): void
    {
        $container = new Chassis();
        $container->bind(Engine::class, ElectricEngine::class);
        $container->alias(Engine::class, 'engine');
        $container->extend(Engine::class, fn (Engine $engine) => new DecoratedEngine($engine));

        $resolved = $container->make('engine');

        $this->assertInstanceOf(DecoratedEngine::class, $resolved);
        $this->assertInstanceOf(ElectricEngine::class, $resolved->inner);
    }

    public function testPsrContainerGetWrapsAnUnknownEntry(): void
    {
        $container = new Chassis();

        $this->expectException(EntryNotFoundException::class);

        $container->get('missing.service');
    }

    public function testBoundMethodsOverrideTheUnderlyingMethod(): void
    {
        $container = new Chassis();
        $service = new CallableService();
        $container->bindMethod(
            [CallableService::class, 'run'],
            fn (CallableService $instance, Chassis $container) => 'bound',
        );

        $this->assertSame('bound', $container->call([$service, 'run']));
    }
}

interface Engine
{
}

class ElectricEngine implements Engine
{
}

class CombustionEngine implements Engine
{
}

class DecoratedEngine implements Engine
{
    public function __construct(public Engine $inner)
    {
    }
}

class SharedService
{
}

class Vehicle
{
    public function __construct(
        public Engine $engine,
        public string $name = 'scrapyard',
    ) {
    }
}

class Generator
{
    public function __construct(public Engine $engine)
    {
    }
}

class CallableService
{
    public function run(): string
    {
        return 'original';
    }
}
