<?php

namespace Fabricate\Sensors;

use ReflectionClass;
use ReflectionException;
use Fabricate\Contracts\Sensors\SensorException;
use Fabricate\Contracts\Sensors\Sensor as SensorInterface;
use Fabricate\Contracts\Sensors\SensorRegistry as RegistryContract;

class SensorRegistry implements RegistryContract
{
    protected array $sensors = [];

    public function __construct() {}

    /**
     * @throws SensorException
     */
    public function type(string $type, string $circuit): SensorInterface
    {
        if(isset($this->sensors[$type])) {
            $sensor_class = $this->sensors[$type];
            return  $sensor_class::circuit($circuit);
        }

        throw new SensorException("Sensor [$type] not registered.");
    }

    public function addSensor(string $name, string $class_name): void
    {
        if($this->validateClassImplementation($class_name))
        {
            $this->sensors[$name] = $class_name;
        }
    }

    public function listSensors(): array
    {
        return $this->sensors;
    }

    protected function validateClassImplementation(string $class_name): bool
    {
        try {
            $reflection = new ReflectionClass($class_name);
        } catch (ReflectionException) {
            return false;
        }

        return $reflection->isSubclassOf(Sensor::class);
    }
}