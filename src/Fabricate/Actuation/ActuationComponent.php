<?php

namespace Fabricate\Actuation;

use Fabricate\Contracts\Actuation\ActuationComponent as ComponentContract;
use Fabricate\IntegratedCircuits\ActuatorIC;

abstract class ActuationComponent implements ComponentContract
{
    abstract public static function buildWith(ActuatorIC $actuator): static;
}
