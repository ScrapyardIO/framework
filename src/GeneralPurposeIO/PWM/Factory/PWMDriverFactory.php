<?php

namespace GeneralPurposeIO\PWM\Factory;

use GeneralPurposeIO\Contracts\PWM\PWMChannelException;
use GeneralPurposeIO\PWM\MultiplePWMChannels;
use GeneralPurposeIO\PWM\PWMChannel;

abstract class PWMDriverFactory
{
    public ?int $channel = null;

    /** @var array<int|string, int|string> */
    public array $addl_channels = [];

    public int|string|null $pwm_chip = null;

    public ?string $name = 'scrapyard-io-pwm';

    abstract public function create(): PWMChannel|MultiplePWMChannels;

    public function channel(int $value): static
    {
        $this->channel = $value;

        return $this;
    }

    public function name(string $value): static
    {
        $this->name = $value;

        return $this;
    }

    public function device(int|string $value): static
    {
        $this->pwm_chip = $value;

        return $this;
    }

    /**
     * @param  array<int|string, int|string>  $addl_channels
     *
     * @throws PWMChannelException
     */
    public function createWith(int|string $device, array $addl_channels): MultiplePWMChannels
    {
        $this->addl_channels = $addl_channels;

        $result = $this->device($device)->create();

        if (! $result instanceof MultiplePWMChannels) {
            throw new PWMChannelException('createWith() requires at least one additional PWM channel.');
        }

        return $result;
    }

    /**
     * @throws PWMChannelException
     */
    protected function assertReady(): void
    {
        if (is_null($this->pwm_chip)) {
            throw PWMChannelException::missingPWMChipDevice();
        }

        if (is_null($this->channel)) {
            throw PWMChannelException::missingChannelOffset();
        }
    }
}
