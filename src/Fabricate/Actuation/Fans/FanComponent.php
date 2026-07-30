<?php

namespace Fabricate\Actuation\Fans;

use Fabricate\Contracts\Actuation\Interfaces\Fan;

class FanComponent implements Fan
{
    public function __construct(protected Fan $fan) {}

    public function actuator(): Fan
    {
        return $this->fan;
    }

    public function on(): void
    {
        $this->fan->on();
    }

    public function off(): void
    {
        $this->fan->off();
    }

    public function speed(?int $percent = null): int
    {
        return $this->fan->speed($percent);
    }

    public function frequency(?int $hz = null): int
    {
        return $this->fan->frequency($hz);
    }

    public function close(): void
    {
        $this->fan->close();
    }
}
