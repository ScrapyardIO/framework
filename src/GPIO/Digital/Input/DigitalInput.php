<?php

namespace GPIO\Digital\Input;

use GPIO\Digital\DigitalPin;
use GPIO\Contracts\Digital\DigitalInputEvent as DigitalInputEventInterface;
use GPIO\Contracts\Digital\DigitalInputDriverAdapter as DigitalInputDriverAdapterInterface;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleInterface;

class DigitalInput extends DigitalPin
{
    public function __construct(
        int $pin,
        string $name,
        DigitalPinConnectionHandleInterface $handle,
        DigitalInputDriverAdapterInterface $driver,
        protected int $timeout_ms,
        protected bool $rising_events,
        protected bool $falling_events,
    ) {
        parent::__construct($pin, $name, $handle, $driver);
    }

    public function getTimeout(): int
    {
        return $this->timeout_ms;
    }

    public function listen(): ?DigitalInputEventInterface
    {
        return $this->driver()->listen($this->getTimeout(), $this->rising_events, $this->falling_events, $this->pin(), $this->handle());
    }

    public function flush(): void
    {
        $stop = false;
        while(!$stop) {
            $stop = is_null($this->listen());
        }
    }

    protected function driver(): DigitalInputDriverAdapterInterface
    {
        /** @var DigitalInputDriverAdapterInterface */
        return $this->driver;
    }
}
