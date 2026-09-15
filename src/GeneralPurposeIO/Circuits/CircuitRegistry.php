<?php

namespace GeneralPurposeIO\Circuits;

use GeneralPurposeIO\Contracts\Circuits\CircuitException;
use GeneralPurposeIO\Contracts\Circuits\CircuitRegistry as RegistryContract;
use GeneralPurposeIO\Contracts\Circuits\IntegratedCircuit;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class CircuitRegistry implements RegistryContract
{
    /**
     * Catalog: IC type slug → class.
     *
     * @var array<string, class-string<IntegratedCircuit>>
     */
    protected array $circuits = [];

    /**
     * IC type slug → artisan command that can scaffold a profile for it.
     *
     * @var array<string, string>
     */
    protected array $profileCommands = [];

    public function __construct() {}

    /**
     * Start a fluent build for a catalog-registered IC type.
     *
     * @throws CircuitException
     */
    public function ic(string $slug): PendingCircuit
    {
        $this->resolveClass($slug);

        return new PendingCircuit($this, $slug);
    }

    /**
     * Build a preprovisioned IC from config/circuits.php profile.
     *
     * Profile keys are arbitrary. Each recipe must include ic, protocol, params.
     *
     * @throws CircuitException
     */
    public function profile(string $name): IntegratedCircuit
    {
        $config = config("circuits.{$name}", null);

        if (is_null($config) || ! is_array($config)) {
            throw new CircuitException("Circuit profile [{$name}] is not defined.");
        }

        $ic = $config['ic'] ?? null;
        $protocol = $config['protocol'] ?? null;
        $params = $config['params'] ?? null;

        if (! is_string($ic) || $ic === '') {
            throw new CircuitException("Circuit profile [{$name}] must define a non-empty ic key.");
        }

        if (! is_string($protocol) || $protocol === '') {
            throw new CircuitException("Circuit profile [{$name}] must define a non-empty protocol.");
        }

        if (! is_array($params)) {
            throw new CircuitException("Circuit profile [{$name}] must define a params array.");
        }

        return $this->build($ic, $protocol, $params);
    }

    /**
     * Invoke the IC class static protocol factory with named params.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws CircuitException
     */
    public function build(string $ic, string $protocol, array $params): IntegratedCircuit
    {
        $class = $this->resolveClass($ic);

        try {
            $method = new ReflectionMethod($class, $protocol);
        } catch (ReflectionException $e) {
            throw new CircuitException(
                "Circuit [{$ic}] class [{$class}] has no static protocol factory [{$protocol}].",
                previous: $e
            );
        }

        if (! $method->isStatic() || ! $method->isPublic()) {
            throw new CircuitException(
                "Circuit [{$ic}] protocol factory [{$protocol}] must be a public static method."
            );
        }

        $named = [];

        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $params)) {
                $named[$name] = $params[$name];

                continue;
            }

            if (! $parameter->isDefaultValueAvailable()) {
                throw new CircuitException(
                    "Circuit [{$ic}] protocol [{$protocol}] is missing required parameter [{$name}]."
                );
            }
        }

        $instance = $method->invokeArgs(null, $named);

        if (! $instance instanceof IntegratedCircuit) {
            throw new CircuitException(
                "Circuit [{$ic}] protocol [{$protocol}] must return an IntegratedCircuit instance."
            );
        }

        return $instance;
    }

    /**
     * @param  class-string<IntegratedCircuit>  $class_name
     */
    public function addCircuit(string $name, string $class_name): void
    {
        if ($this->validateClassImplementation($class_name)) {
            $this->circuits[$name] = $class_name;
        }
    }

    /**
     * Register a package artisan command that scaffolds profiles for an IC slug.
     * Used by `circuit:make-profile` to delegate (e.g. st7789 → st77xx:make-profile).
     */
    public function registerProfileCommand(string $ic, string $artisanCommand): void
    {
        $this->profileCommands[$ic] = $artisanCommand;
    }

    public function profileCommand(string $ic): ?string
    {
        return $this->profileCommands[$ic] ?? null;
    }

    /**
     * @return array<string, class-string<IntegratedCircuit>>
     */
    public function listCircuits(): array
    {
        return $this->circuits;
    }

    /**
     * @return class-string<IntegratedCircuit>
     *
     * @throws CircuitException
     */
    public function resolveClass(string $slug): string
    {
        if (! isset($this->circuits[$slug])) {
            throw new CircuitException("Circuit [{$slug}] is not registered.");
        }

        return $this->circuits[$slug];
    }

    protected function validateClassImplementation(string $class_name): bool
    {
        try {
            $reflection = new ReflectionClass($class_name);
        } catch (ReflectionException) {
            return false;
        }

        return $reflection->implementsInterface(IntegratedCircuit::class);
    }
}
