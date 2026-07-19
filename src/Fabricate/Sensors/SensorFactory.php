<?php

namespace Fabricate\Sensors;

use Fabricate\Contracts\Chassis\Chassis;
use Fabricate\Contracts\Sensors\SensorException;
use Fabricate\Sensors\Components\Accelerometer;
use Fabricate\Sensors\Components\HumiditySensor;
use Fabricate\Sensors\Components\TemperatureSensor;

class SensorFactory
{
    public ?string $sensor_type = null;
    public ?string $circuit_config_slug = null;

    public function __construct(
        protected Chassis $machine,
        public readonly array $sensor_config
    )
    {
    }

    public function type(string $type): static
    {
        $this->sensor_type = $type;
        return $this;
    }

    /**
     * @param string $circuit
     * @return $this
     * @throws SensorException
     */
    public function circuit(string $circuit): static
    {
        if(isset($this->sensor_config[$circuit])) {
            $this->circuit_config_slug = $circuit;
            return $this;
        }

        throw new SensorException("Circuit $circuit not registered.");
    }

    /**
     * @throws SensorException
     */
    public function get(): SensorComponent
    {
        $this->validateRequiredParams();
        /** @var class-string<SensorComponent> $component_class */
        $component_class = $this->getComponentClass();
        $this->assertTypeMatchesComponent($component_class);
        $sensor_class = $this->getCircuitClass();
        $transport_config = $this->getTransportParams();
        $protocol = $transport_config['protocol'];
        $sensor = $sensor_class::{$protocol}(...$transport_config['args']);

        return $component_class::buildWith($sensor);
    }

    /**
     * @throws SensorException
     */
    protected function validateRequiredParams(): void
    {
        if(is_null($this->sensor_type)) {
            throw new SensorException("Sensor type is required.");
        }

        if(is_null($this->circuit_config_slug)) {
            throw new SensorException("Circuit config slug is required.");
        }
    }

    /**
     * @param  class-string<SensorComponent>  $component_class
     *
     * @throws SensorException
     */
    protected function assertTypeMatchesComponent(string $component_class): void
    {
        $expected = $this->componentClassForType($this->sensor_type);

        if ($component_class !== $expected && ! is_subclass_of($component_class, $expected)) {
            throw SensorException::typeComponentMismatch(
                $this->sensor_type,
                $expected,
                $component_class,
                $this->circuit_config_slug,
            );
        }
    }

    /**
     * @return class-string<SensorComponent>
     *
     * @throws SensorException
     */
    protected function componentClassForType(string $type): string
    {
        return match ($type) {
            'temperature' => TemperatureSensor::class,
            'humidity' => HumiditySensor::class,
            'accelerometer' => Accelerometer::class,
            default => throw SensorException::unknownType($type),
        };
    }

    /**
     * @return string
     * @throws SensorException
     */
    protected function getComponentClass(): string
    {
        if(isset($this->sensor_config[$this->circuit_config_slug]['component']))
        {
            return $this->sensor_config[$this->circuit_config_slug]['component'];
        }

        throw new SensorException("Config missing sensor component.");
    }

    /**
     * @return string
     * @throws SensorException
     */
    protected function getCircuitClass(): string
    {
        if(isset($this->sensor_config[$this->circuit_config_slug]['circuit']))
        {
            return $this->sensor_config[$this->circuit_config_slug]['circuit'];
        }

        throw new SensorException("Config missing circuit class.");
    }

    /**
     * @return array
     * @throws SensorException
     */
    protected function getTransportParams(): array
    {
        if(isset($this->sensor_config[$this->circuit_config_slug]['params']))
        {
            if(isset($this->sensor_config[$this->circuit_config_slug]['params']['transport']))
            {
                return $this->sensor_config[$this->circuit_config_slug]['params']['transport'];
            }

            throw new SensorException("Config missing transport key in params array");
        }

        throw new SensorException("Config missing params array");
    }
}
