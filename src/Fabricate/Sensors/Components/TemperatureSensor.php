<?php

namespace Fabricate\Sensors\Components;

use Fabricate\Contracts\Sensors\Enums\TemperatureUnit;
use Fabricate\Contracts\Sensors\SensorException;
use Fabricate\Contracts\Sensors\TemperatureMeasurements;
use Fabricate\IntegratedCircuits\SensorIC;
use Fabricate\Sensors\SensorComponent;

/**
 * @property-read float $temperature
 */
class TemperatureSensor extends SensorComponent
{
    public function __construct(
        protected TemperatureMeasurements $circuit
    ) {}

    /**
     * @throws SensorException
     */
    public function __get(string $name): float
    {
        return match ($name) {
            'temperature' => $this->getTemperature(),
            default => throw SensorException::invalidProperty($name, static::class),
        };
    }

    public function getTemperature(TemperatureUnit|string $unit = TemperatureUnit::CELSIUS): float
    {
        if(is_string($unit)) {
            $unit = TemperatureUnit::tryFrom($unit);
        }
        return $this->circuit->measureTemp($unit);
    }

    /**
     * @throws SensorException
     */
    public static function buildWith(SensorIC $sensor): static
    {
        if($sensor instanceof TemperatureMeasurements) {
            return new static($sensor);
        }

        throw new SensorException($sensor::class.' must implement TemperatureMeasurements');
    }
}
