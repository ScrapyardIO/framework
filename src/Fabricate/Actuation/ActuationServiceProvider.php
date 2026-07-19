<?php

namespace Fabricate\Actuation;

use Fabricate\Chassis\EntryNotFoundException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Support\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

class ActuationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @throws ReflectionException
     */
    public function register(): void
    {
        app()->singleton('actuator', fn ($app) => new ActuatorFactory($app, $this->actuatorConfig()));
    }

    public function boot(): void
    {
    }

    public function provides(): array
    {
        return ['actuator', ActuatorFactory::class];
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     */
    protected function actuatorConfig(): array
    {
        return config('integrated-circuits.actuators', []);
    }
}
