<?php

namespace Fabricate\Actuation\Components;

use Fabricate\Contracts\Actuation\ActuationException;
use Fabricate\Contracts\Actuation\Fans\FanSpeedControl;
use Fabricate\Contracts\Actuation\Fans\FanSpeedControl as FanSpeedControlInterface;
use Fabricate\IntegratedCircuits\ActuatorIC;
use Fabricate\Sensors\Components\TachometerComponent;

class SpeedControllableFan extends Fan implements FanSpeedControl
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

    public static function buildWith(ActuatorIC $actuator): static
    {
        if ($actuator instanceof FanSpeedControlInterface) {
            return new static($actuator);
        }

        throw new ActuationException($actuator::class.' must implement FanSpeedControl');
    }
}
