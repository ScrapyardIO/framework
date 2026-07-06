<?php

namespace GPIO\Digital;

use GPIO\Common\GPIOTransport;
use GPIO\Contracts\Digital\DigitalPinTransport as DigitalPinTransportContract;
use GPIO\Contracts\Digital\DigitalPinDriverAdapter as DigitalPinDriverAdapterInterface;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleInterface;

abstract class DigitalPin extends GPIOTransport implements DigitalPinTransportContract
{
    public function __construct(
        private readonly int $pin,
        private readonly string $name,
        DigitalPinConnectionHandleInterface $handle,
        DigitalPinDriverAdapterInterface $driver
    ) {
        parent::__construct($driver, $handle);
    }

    abstract protected function driver():DigitalPinDriverAdapterInterface;

    public function isLow(): bool
    {
        /** @var */
        return !$this->driver()->read($this->pin(), $this->handle());
    }

    public function isHigh(): bool
    {
        return $this->driver()->read($this->pin(), $this->handle());
    }

    protected function pin(): int
    {
        return $this->pin;
    }

    protected function name(): string
    {
        return $this->name;
    }

    protected function handle(): DigitalPinConnectionHandleInterface
    {
        /** @var DigitalPinConnectionHandleInterface */
        return $this->handle;
    }
}
