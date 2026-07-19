<?php

namespace Fabricate\Sensors;

use Fabricate\Chassis\EntryNotFoundException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Support\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

class SensorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @throws ReflectionException
     */
    public function register(): void
    {
        app()->singleton('sensor', fn($app) => new SensorFactory($app, $this->sensorConfig()));
    }

    public function boot(): void
    {

    }

    public function provides(): array
    {
        return ['sensor', SensorFactory::class];
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     */
    protected function sensorConfig(): array
    {
        return config('integrated-circuits.sensors', []);
    }
}
