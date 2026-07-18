<?php

namespace GeneralPurposeIO\Digital\Factory;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\Digital\DigitalPinException;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Digital\DigitalInput;
use GeneralPurposeIO\Digital\Drivers\PosixDigitalPinDriver;
use GeneralPurposeIO\Digital\MultipleDigitalPins;
use Microscrap\Bindings\GPIO\Enums\LineDirection;

class PosixDigitalInputDriverFactory extends PosixDigitalPinDriverFactory
{
    public int $timeout_ms = 1000;

    public bool $active_low = false;

    public bool $rising_events = false;

    public bool $falling_events = false;

    public LineBias $line_bias = LineBias::AS_IS;

    /**
     * @throws DigitalPinException
     * @throws GPIOException
     */
    public function create(): DigitalInput|MultipleDigitalPins
    {
        $this->assertReady();

        if (count($this->addl_pins) > 0) {
            return $this->createPosixPinBus();
        }

        $has_nonblocking_input = $this->timeout_ms > -1;
        $line_config = gpiod_line_config_new();

        $this->addLineSettings(
            $line_config,
            LineDirection::INPUT,
            $this->pin,
            $this->line_bias,
            $this->active_low,
            static::edgeEvents($this->rising_events, $this->falling_events),
        );

        $line_request = $this->createLineRequest(
            (int) $this->device,
            $this->consumer(),
            $line_config,
            $has_nonblocking_input,
        );

        return new DigitalInput(
            $this->pin,
            $this->consumer(),
            new PosixDigitalPinDriver($line_request),
            $this->timeout_ms,
            $this->rising_events,
            $this->falling_events,
        );
    }

    public function timeout(int $timeout_ms): static
    {
        $this->timeout_ms = $timeout_ms;

        return $this;
    }

    public function withEvents(bool $rising, bool $falling): static
    {
        $this->rising_events = $rising;
        $this->falling_events = $falling;

        return $this;
    }

    public function lineBias(LineBias $line_bias): static
    {
        $this->line_bias = $line_bias;

        return $this;
    }

    public function activeLow(): static
    {
        $this->active_low = true;

        return $this;
    }
}
