<?php

namespace BareMetal\Sensors;

use BareMetal\Circuits\GPIOManager;
use GPIO\Common\GPIO;
use GPIO\Contracts\Common\GPIOConnectionFactory;
use ScrpayardIO\NutsAndBolts\Collection;
use BareMetal\Contracts\Sensors\Sensor as SensorInterface;
use BareMetal\Contracts\Sensors\SensorComponent as SensorComponentInterface;
use BareMetal\Contracts\Sensors\SensorService as SensorServiceContract;

class SensorService implements SensorServiceContract
{
    protected Collection $active_sensors;

    public function __construct() {
        $this->active_sensors = collect();
    }

    public function enabled(string $library): bool
    {
        return config("{$library}.enabled", false);
    }

    public function sensorConfig(string $sensor, string $library = null): ?array
    {
        $sensor = (!is_null($library)) ? $this->sensorFromLibrary($sensor, $library) : $sensor;
        if($sensor)
        {
            return config("integrated-circuits.{$sensor}", null);
        }

        return null;
    }

    public function sensorFromLibrary(string $sensor, string $library): ?string
    {
        return ($sensor == 'default') ? $this->defaultSensorFromLibrary($library) : strtolower($sensor);
    }

    public function addToActiveSensors(array $config, SensorComponentInterface $component): void
    {
        $new_sensor = $this->buildSensor($config, $component);
        $this->active_sensors->push($new_sensor);
    }

    public function defaultSensorFromLibrary(string $library): ?string
    {
        return config($library)['default'] ?? null;
    }

    protected function buildSensor(array $config, SensorComponentInterface $component): SensorInterface
    {
        $protocol = GPIOManager::findMainProtocol($config['bus']);
        $driver = $config['bus'][$protocol]['driver'];

        return GPIOManager::manage($protocol, $driver, $config, $component);
    }
}
