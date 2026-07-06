<?php

namespace BareMetal\Sensors;

use BareMetal\Circuits\IntegratedCircuit;
use BareMetal\Contracts\Sensors\Sensor as SensorContract;

abstract class Sensor extends IntegratedCircuit implements SensorContract
{

}
