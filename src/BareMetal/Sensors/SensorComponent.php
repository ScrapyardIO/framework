<?php

namespace BareMetal\Sensors;

use BareMetal\Contracts\Sensors\SensorComponent as SensorComponentContract;
use BareMetal\Contracts\Sensors\SensorService;
use ScrpayardIO\NutsAndBolts\Collection;

abstract class SensorComponent implements SensorComponentContract
{


    public function __construct(protected SensorService $sensor_service)
    {}

    abstract public function get(): static;
    abstract public function sensor(string $sensor = 'default'): static;
}
