<?php

namespace ScrapyardIO\NutsAndBolts;

use UnitEnum;
use RuntimeException;
use ReflectionException;
use InvalidArgumentException;
use BareMetal\Contracts\Chassis\Chassis;
use BareMetal\Contracts\Config\Repository;
use BareMetal\Contracts\Chassis\BindingResolutionException;
use ScrapyardIO\NutsAndBolts\Concerns\RebindsCallbacksToSelf;

abstract class Manager
{
    use RebindsCallbacksToSelf;

    /**
     * The container instance.
     */
    protected Chassis $container;

    /**
     * The configuration repository instance.
     */
    protected Repository $config;

    /**
     * The registered custom driver creators.
     */
    protected array $custom_creators = [];

    /**
     * The array of created "drivers".
     */
    protected array $drivers = [];

    /**
     * Create a new manager instance.
     * @throws BindingResolutionException
     */
    public function __construct(Chassis $container)
    {
        $this->container = $container;
        $this->config = $container->make('config');
    }

    /**
     * Get the default driver name.
     */
    abstract public function getDefaultDriver(): ?string;

    /**
     * Get a driver instance.
     * @throws InvalidArgumentException
     */
    public function driver(UnitEnum|string|null $driver = null): mixed
    {
        $driver = enum_value($driver) ?: $this->getDefaultDriver();

        if (is_null($driver)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].', static::class
            ));
        }

        // If the given driver has not been created before, we will create the instances
        // here and cache it so we can return it next time very quickly. If there is
        // already a driver created by this name, we'll just return that instance.
        return $this->drivers[$driver] ??= $this->createDriver($driver);
    }

    /**
     * Create a new driver instance.
     * @throws InvalidArgumentException
     */
    protected function createDriver(string $driver): mixed
    {
        // First, we will determine if a custom driver creator exists for the given driver and
        // if it does not we will check for a creator method for the driver. Custom creator
        // callbacks allow developers to build their own "drivers" easily using Closures.
        if (isset($this->custom_creators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        $method = 'create'.Str::studly($driver).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }

    /**
     * Call a custom driver creator.
     */
    protected function callCustomCreator(string $driver): mixed
    {
        return $this->custom_creators[$driver]($this->container);
    }

    /**
     * Register a custom driver creator Closure.
     */
    public function extend($driver, callable $callback): static
    {
        try {
            $callback = $this->bindCallbackToSelf($callback) ?? throw new RuntimeException('Unable to bind custom driver callback');
        } catch (ReflectionException $e) {
            throw new RuntimeException('Unable to bind custom driver callback', previous: $e);
        }

        $this->custom_creators[$driver] = $callback;

        return $this;
    }

    /**
     * Get every created "driver".
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * Get the container instance used by the manager.
     */
    public function getContainer(): Chassis
    {
        return $this->container;
    }

    /**
     * Set the container instance used by the manager.
     */
    public function setContainer(Chassis $container): static
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Forget every resolved driver instance.
     *
     * @return $this
     */
    public function forgetDrivers(): static
    {
        $this->drivers = [];

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }
}
