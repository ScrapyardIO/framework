<?php

namespace RealityInterface\Sensors\Repositories;

use BareMetal\Repositories\IntegratedCircuitRepository;
use Exception;
use RealityInterface\Sensors\Exceptions\SensorRepoException;
use RealityInterface\Sensors\SensorChip;

class SensorChipRepository extends IntegratedCircuitRepository
{
    protected function __construct()
    {
        $this->config = $this->loadConfig();
    }

    public function hasSensor(string $sensor_name): bool
    {
        return isset($this->config['sensors'][$sensor_name]);
    }

    public function getSensor(string $circuit_name): SensorChip|false
    {
        $circuit_def = $this->config['sensors'][$circuit_name];

        try {
            $class_name = $circuit_def['class_name'];
            $connection = $circuit_def['connection'];

            $circuit = $class_name::connection(...$connection);
            $startup = $circuit_def['startup'];
            foreach ($startup as $cmd => $args) {
                $circuit = $circuit->$cmd(...$args);
            }

            $results = $circuit->create();
        } catch (Exception $e) {
            $results = false;
        }

        return $results;
    }

    public static function sensor(string $sensor_name): SensorChip
    {
        $repo = static::getInstance();
        if ($repo->hasSensor($sensor_name)) {
            $sensor = $repo->getSensor($sensor_name);

            if ($sensor instanceof SensorChip) {
                return $sensor;
            }
        }

        throw SensorRepoException::sensorChipNotRegistered($sensor_name);
    }
}
