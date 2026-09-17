<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\PWM\PWMTransport;

class FakePWMTransport extends PWMTransport
{
    public int $period = 0;

    public int $duty_cycle = 0;

    public bool $enabled = false;

    public bool $inversed = false;

    public function __construct(int $channel, public readonly FakeHandle $handle)
    {
        parent::__construct($channel);
    }

    public function handle(): FakeHandle
    {
        return $this->handle;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function setPeriod(int $value): int
    {
        return $this->period = $value;
    }

    public function getEnable(): bool
    {
        return $this->enabled;
    }

    public function setEnable(bool $value): bool
    {
        return $this->enabled = $value;
    }

    public function getDutyCycle(): int
    {
        return $this->duty_cycle;
    }

    public function setDutyCycle(int $value): int
    {
        return $this->duty_cycle = $value;
    }

    public function getPolarity(): bool
    {
        return $this->inversed;
    }

    public function setPolarity(bool $value): bool
    {
        return $this->inversed = $value;
    }

    public function close(): void
    {
        $this->enabled = false;
        $this->handle->closed = true;
    }
}
