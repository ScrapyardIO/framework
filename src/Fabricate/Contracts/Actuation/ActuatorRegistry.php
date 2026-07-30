<?php

namespace Fabricate\Contracts\Actuation;

interface ActuatorRegistry
{
    public function type(string $type, string $circuit): Actuator;

    /**
     * @param  class-string<Actuator>  $class_name
     */
    public function addActuator(string $name, string $class_name): void;

    /**
     * @return array<string, class-string<Actuator>>
     */
    public function listActuators(): array;
}