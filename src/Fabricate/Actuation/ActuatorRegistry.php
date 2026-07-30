<?php

namespace Fabricate\Actuation;

use Fabricate\Contracts\Actuation\Actuator as ActuatorInterface;
use Fabricate\Contracts\Actuation\ActuatorException;
use Fabricate\Contracts\Actuation\ActuatorRegistry as RegistryContract;
use ReflectionClass;
use ReflectionException;

class ActuatorRegistry implements RegistryContract
{
    /**
     * @var array<string, class-string<ActuatorInterface>>
     */
    protected array $actuators = [];

    public function __construct() {}

    /**
     * @throws ActuatorException
     */
    public function type(string $type, string $circuit): ActuatorInterface
    {
        if (isset($this->actuators[$type])) {
            $actuator_class = $this->actuators[$type];

            return $actuator_class::circuit($circuit);
        }

        throw new ActuatorException("Actuator [$type] not registered.");
    }

    public function addActuator(string $name, string $class_name): void
    {
        if ($this->validateClassImplementation($class_name)) {
            $this->actuators[$name] = $class_name;
        }
    }

    public function listActuators(): array
    {
        return $this->actuators;
    }

    protected function validateClassImplementation(string $class_name): bool
    {
        try {
            $reflection = new ReflectionClass($class_name);
        } catch (ReflectionException) {
            return false;
        }

        return $reflection->implementsInterface(ActuatorInterface::class)
            && ! $reflection->isAbstract();
    }
}
