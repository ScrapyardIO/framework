<?php

namespace BareMetal\Actuation\Fans;

use BareMetal\Sensors\Speed\TachometerComponent;
use BareMetal\Contracts\Actuators\ActuationException;
use BareMetal\Contracts\Actuators\Fans\FanSpeedControl;
use BareMetal\Contracts\Actuators\Fans\FanSpeedControl as FanSpeedControlInterface;


class SpeedControllableFan extends FanComponent implements FanSpeedControl
{
    public function __construct(
        protected FanSpeedControlInterface $fan,
        protected ?TachometerComponent $tachometer = null,
    ) {}

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

    /**
     * @throws ActuationException
     */
    public function rpm(int $sample_ms = 500, int $pulses_per_revolution = 2): float
    {
        if (is_null($this->tachometer)) {
            throw ActuationException::tachometerNotAttached(static::class);
        }

        return $this->tachometer->rpm($sample_ms, $pulses_per_revolution);
    }

    public function hasTachometer(): bool
    {
        return ! is_null($this->tachometer);
    }
}
