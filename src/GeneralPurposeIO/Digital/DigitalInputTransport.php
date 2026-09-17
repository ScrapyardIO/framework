<?php

namespace GeneralPurposeIO\Digital;

use GeneralPurposeIO\Contracts\Core\EdgeSource;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport as TransportContract;

/** Every input pin is an EdgeSource the gpio dock can watch; the driver that hands it out binds it to its device. */
abstract class DigitalInputTransport implements TransportContract, EdgeSource
{
    protected string|int|null $device = null;

    public function __construct(
        public readonly int $pin,
    ) {}

    public function offset(): int
    {
        return $this->pin;
    }

    public function device(): string|int|null
    {
        return $this->device;
    }

    /** Wire-internal: the connection driver names the device this pin rides on. */
    public function boundTo(string|int $device): static
    {
        $this->device = $device;

        return $this;
    }
}
