<?php

namespace GPIO\Digital\Output;

use GPIO\Contracts\Digital\DigitalOutputDriverAdapter as DigitalOutputDriverAdapterInterface;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleInterface;
use GPIO\Digital\DigitalPin;

class DigitalOutput extends DigitalPin
{
    public function __construct(
        int $pin,
        string $name,
        DigitalPinConnectionHandleInterface $handle,
        DigitalOutputDriverAdapterInterface $driver,
        protected readonly bool $default_state,
    ) {
        parent::__construct($pin, $name, $handle, $driver);
        $this->default_state ? $this->high() : $this->low();
    }

    public function low(): void
    {
        /** @var DigitalOutputDriverAdapterInterface $driver */
        $driver = $this->driver;

        $driver->write($this->pin(), false, $this->handle());
    }

    public function high(): void
    {
        /** @var DigitalOutputDriverAdapterInterface $driver */
        $driver = $this->driver;

        $driver->write($this->pin(), true, $this->handle());
    }

    protected function driver(): DigitalOutputDriverAdapterInterface
    {
        /** @var DigitalOutputDriverAdapterInterface */
        return $this->driver;
    }
}
