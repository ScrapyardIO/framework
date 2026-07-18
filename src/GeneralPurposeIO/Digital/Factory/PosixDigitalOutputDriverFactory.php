<?php

namespace GeneralPurposeIO\Digital\Factory;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\Digital\DigitalPinException;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Digital\DigitalOutput;
use GeneralPurposeIO\Digital\Drivers\PosixDigitalPinDriver;
use GeneralPurposeIO\Digital\MultipleDigitalPins;
use Microscrap\Bindings\GPIO\Enums\LineDirection;

class PosixDigitalOutputDriverFactory extends PosixDigitalPinDriverFactory
{
    public bool $default_state = false;

    /**
     * @throws DigitalPinException
     * @throws GPIOException
     */
    public function create(): DigitalOutput|MultipleDigitalPins
    {
        $this->assertReady();

        if (count($this->addl_pins) > 0) {
            return $this->createPosixPinBus();
        }

        $line_config = gpiod_line_config_new();

        $this->addLineSettings(
            $line_config,
            LineDirection::OUTPUT,
            $this->pin,
            LineBias::AS_IS,
            false,
        );

        $line_request = $this->createLineRequest(
            (int) $this->device,
            $this->consumer(),
            $line_config,
        );

        return new DigitalOutput(
            $this->pin,
            $this->consumer(),
            new PosixDigitalPinDriver($line_request),
            $this->default_state,
        );
    }

    public function defaultState(bool $state): static
    {
        $this->default_state = $state;

        return $this;
    }
}
