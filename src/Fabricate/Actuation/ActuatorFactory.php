<?php

namespace Fabricate\Actuation;

use Fabricate\Actuation\Components\BasicFan;
use Fabricate\Actuation\Components\BasicInput;
use Fabricate\Actuation\Components\ContinuousServo;
use Fabricate\Actuation\Components\DigitalInputPad;
use Fabricate\Actuation\Components\PositionalServo;
use Fabricate\Actuation\Components\SpeedControllableFan;
use Fabricate\Contracts\Actuation\ActuationException;
use Fabricate\Contracts\Chassis\Chassis;

class ActuatorFactory
{
    public ?string $actuator_type = null;

    public ?string $circuit_config_slug = null;

    public function __construct(
        protected Chassis $machine,
        public readonly array $actuator_config,
    ) {}

    public function type(string $type): static
    {
        $this->actuator_type = $type;

        return $this;
    }

    /**
     * @throws ActuationException
     */
    public function circuit(string $circuit): static
    {
        if (isset($this->actuator_config[$circuit])) {
            $this->circuit_config_slug = $circuit;

            return $this;
        }

        throw new ActuationException("Circuit $circuit not registered.");
    }

    /**
     * @throws ActuationException
     */
    public function get(): ActuationComponent
    {
        $this->validateRequiredParams();
        /** @var class-string<ActuationComponent> $component_class */
        $component_class = $this->getComponentClass();
        $this->assertTypeMatchesComponent($component_class);
        $actuator_class = $this->getCircuitClass();
        $transport_config = $this->getTransportParams();
        $protocol = $transport_config['protocol'];
        $actuator = $actuator_class::{$protocol}(...$transport_config['args']);

        return $component_class::buildWith($actuator);
    }

    /**
     * @throws ActuationException
     */
    protected function validateRequiredParams(): void
    {
        if (is_null($this->actuator_type)) {
            throw new ActuationException('Actuator type is required.');
        }

        if (is_null($this->circuit_config_slug)) {
            throw new ActuationException('Circuit config slug is required.');
        }
    }

    /**
     * @param  class-string<ActuationComponent>  $component_class
     *
     * @throws ActuationException
     */
    protected function assertTypeMatchesComponent(string $component_class): void
    {
        $expected = $this->componentClassForType($this->actuator_type);

        if ($component_class !== $expected && ! is_subclass_of($component_class, $expected)) {
            throw ActuationException::typeComponentMismatch(
                $this->actuator_type,
                $expected,
                $component_class,
                $this->circuit_config_slug,
            );
        }
    }

    /**
     * @return class-string<ActuationComponent>
     *
     * @throws ActuationException
     */
    protected function componentClassForType(string $type): string
    {
        return match ($type) {
            'positional-servo' => PositionalServo::class,
            'continuous-servo' => ContinuousServo::class,
            'fan' => BasicFan::class,
            'speed-controllable-fan' => SpeedControllableFan::class,
            'basic-input' => BasicInput::class,
            'input-pad' => DigitalInputPad::class,
            default => throw ActuationException::unknownType($type),
        };
    }

    /**
     * @throws ActuationException
     */
    protected function getComponentClass(): string
    {
        if (isset($this->actuator_config[$this->circuit_config_slug]['component'])) {
            return $this->actuator_config[$this->circuit_config_slug]['component'];
        }

        throw new ActuationException('Config missing actuator component.');
    }

    /**
     * @throws ActuationException
     */
    protected function getCircuitClass(): string
    {
        if (isset($this->actuator_config[$this->circuit_config_slug]['circuit'])) {
            return $this->actuator_config[$this->circuit_config_slug]['circuit'];
        }

        throw new ActuationException('Config missing circuit class.');
    }

    /**
     * @throws ActuationException
     */
    protected function getTransportParams(): array
    {
        if (isset($this->actuator_config[$this->circuit_config_slug]['params'])) {
            if (isset($this->actuator_config[$this->circuit_config_slug]['params']['transport'])) {
                return $this->actuator_config[$this->circuit_config_slug]['params']['transport'];
            }

            throw new ActuationException('Config missing transport key in params array');
        }

        throw new ActuationException('Config missing params array');
    }
}
