<?php

namespace BareMetal\Actuation;

use BareMetal\Circuits\IntegratedCircuit;
use BareMetal\Contracts\Actuators\Actuator as ActuatorContract;

abstract class Actuator extends IntegratedCircuit implements ActuatorContract
{

}
