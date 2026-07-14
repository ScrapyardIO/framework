<?php

namespace BareMetal\Contracts\Sensors;

use BareMetal\Contracts\Sensors\Sensor as SensorInterface;

interface SensorService
{
    public function enabled(string $library): bool;
    public function addToActiveSensors(array $config, SensorComponent $component): void;
    public function sensorConfig(string $sensor, ?string $library = null): ?array;
}
