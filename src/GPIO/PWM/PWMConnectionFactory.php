<?php

namespace GPIO\PWM;

use GPIO\Common\GPIOConnectionFactory;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\PWM\PWMChannelException;
use GPIO\Contracts\PWM\PWMDriverAdapter as PWMDriverAdapterInterface;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;
use GPIO\Contracts\PWM\PWMConnectionFactory as PWMConnectionFactoryContract;

#[CarrierFactory('pwm')]
class PWMConnectionFactory extends GPIOConnectionFactory implements PWMConnectionFactoryContract
{
    /** @var PWMDriverAdapterInterface */
    protected GPIODriverAdapterInterface $driver_adapter;

    public ?int $channel = null;

    public array $addl_channels = [];

    public int|string|null $pwm_chip = null;

    public ?string $name = 'scrapyard-io-pwm';

    public function __construct(
        string $driver
    ) {
        parent::__construct($driver);
    }

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
     * @throws PWMChannelException
     */
    public function createWith(int|string $device, array $addl_channels): MultiplePWMChannels
    {
        $this->addl_channels = $addl_channels;
        return $this->device($device)->create();
    }

    /**
     * @throws PWMChannelException
     */
    public function create(): PWMChannel|MultiplePWMChannels
    {
        if(is_null($this->pwm_chip)) {
            throw PWMChannelException::missingPWMChipDevice();
        }

        if(is_null($this->channel)) {
            throw PWMChannelException::missingChannelOffset();
        }

        return $this->driver_adapter->buildConnection(
            $this->pwm_chip,
            $this->channel,
            $this->name,
            $this->addl_channels
        );
    }
}
