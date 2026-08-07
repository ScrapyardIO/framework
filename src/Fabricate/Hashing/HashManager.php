<?php

namespace Fabricate\Hashing;

use Closure;
use Fabricate\Contracts\Config\Repository as ConfigRepository;
use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\Hashing\Hasher;
use Fabricate\Hashing\Concerns\RebindsCallbacksToSelf;
use Fabricate\NutsAndBolts\Str;
use InvalidArgumentException;
use ReflectionException;
use RuntimeException;

use function Fabricate\NutsAndBolts\Helpers\enum_value;

/**
 * @mixin \Fabricate\Contracts\Hashing\Hasher
 */
class HashManager implements Hasher
{
    use RebindsCallbacksToSelf;

    protected Program $container;

    protected ConfigRepository $config;

    /**
     * @var array<string, \Closure>
     */
    protected array $customCreators = [];

    /**
     * @var array<string, Hasher>
     */
    protected array $drivers = [];

    public function __construct(Program $container)
    {
        $this->container = $container;
        $this->config = $container->make('config');
    }

    public function createBcryptDriver()
    {
        return new BcryptHasher($this->config->get('hashing.bcrypt') ?? []);
    }

    public function createArgonDriver()
    {
        return new ArgonHasher($this->config->get('hashing.argon') ?? []);
    }

    public function createArgon2idDriver()
    {
        return new Argon2IdHasher($this->config->get('hashing.argon') ?? []);
    }

    public function info($hashedValue)
    {
        return $this->driver()->info($hashedValue);
    }

    public function make(#[\SensitiveParameter] $value, array $options = [])
    {
        return $this->driver()->make($value, $options);
    }

    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = [])
    {
        return $this->driver()->check($value, $hashedValue, $options);
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        return $this->driver()->needsRehash($hashedValue, $options);
    }

    public function isHashed(#[\SensitiveParameter] $value)
    {
        return $this->driver()->info($value)['algo'] !== null;
    }

    public function getDefaultDriver()
    {
        return $this->config->get('hashing.driver', 'bcrypt');
    }

    /**
     * @internal
     */
    public function verifyConfiguration($value)
    {
        if (method_exists($driver = $this->driver(), 'verifyConfiguration')) {
            return $driver->verifyConfiguration($value);
        }

        return true;
    }

    /**
     * @param  \UnitEnum|string|null  $driver
     */
    public function driver($driver = null)
    {
        $driver = enum_value($driver) ?: $this->getDefaultDriver();

        if (is_null($driver)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].', static::class
            ));
        }

        return $this->drivers[$driver] ??= $this->createDriver($driver);
    }

    protected function createDriver(string $driver)
    {
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        $method = 'create'.Str::studly($driver).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }

    protected function callCustomCreator(string $driver)
    {
        return $this->customCreators[$driver]($this->container);
    }

    /**
     * @param-closure-this  $this  $callback
     */
    public function extend(string $driver, Closure $callback)
    {
        try {
            $callback = $this->bindCallbackToSelf($callback) ?? throw new RuntimeException('Unable to bind custom driver callback');
        } catch (ReflectionException $e) {
            throw new RuntimeException('Unable to bind custom driver callback', previous: $e);
        }

        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * @return array<string, Hasher>
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function setContainer(Program $container)
    {
        $this->container = $container;

        return $this;
    }

    public function forgetDrivers()
    {
        $this->drivers = [];

        return $this;
    }

    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
