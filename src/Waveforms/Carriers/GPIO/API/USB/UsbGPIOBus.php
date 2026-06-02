<?php

namespace Waveforms\Carriers\GPIO\API\USB;

use Microscrap\Bindings\MPSSE\MPSSEContext;
use Waveforms\Carriers\GPIO\Exceptions\GPIOException;
use Waveforms\Carriers\GPIO\GPIOBus;

class UsbGPIOBus extends GPIOBus
{
    public function __construct(
        public readonly MPSSEContext $chip,
        public readonly array $pins = []
    ) {}

    public function __call(string $name, array $arguments): UsbGPIOPanel
    {
        if (isset($this->pins[$name])) {
            return new UsbGPIOPanel($this->chip, $this->pins[$name]->pin);
        }

        throw GPIOException::pinAliasNotFound($name);
    }
}
