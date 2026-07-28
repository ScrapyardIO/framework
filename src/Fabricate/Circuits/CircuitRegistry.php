<?php

namespace Fabricate\Circuits;

use ReflectionClass;
use ReflectionException;
use Fabricate\Contracts\Circuits\CircuitException;
use Fabricate\Contracts\Circuits\IntegratedCircuit;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Circuits\CircuitRegistry as RegistryContract;

class CircuitRegistry implements RegistryContract
{
    protected array $circuits = [];

    public function __construct() {}

    /**
     * @throws CircularDependencyException
     * @throws CircuitException
     */
    public function driver(string $driver): IntegratedCircuit
    {
        if(isset($this->circuits[$driver])) {
            $config = config("circuits.{$driver}", null);
            if(!is_null($config)) {
                $driver_class = $this->circuits[$driver];
                [$protocol, $params] = array_values($config);
                return $driver_class::$protocol(...$params);
            }

            throw new CircuitException("Circuit [$driver] has no build configuration defined.");
        }

        throw new CircuitException("Circuit [$driver] not registered.");
    }

    public function addCircuit(string $name, string $class_name): void
    {
        if($this->validateClassImplementation($class_name))
        {
            $this->circuits[$name] = $class_name;
        }
    }

    public function listCircuits(): array
    {
        return $this->circuits;
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