<?php

namespace GeneralPurposeIO\PWM\Factory;

use GeneralPurposeIO\Contracts\PWM\PWMChannelException;
use GeneralPurposeIO\PWM\Drivers\NativePWMDriver;
use GeneralPurposeIO\PWM\MultiplePWMChannels;
use GeneralPurposeIO\PWM\PWMChannel;

class NativePWMDriverFactory extends PWMDriverFactory
{
    /**
     * @throws PWMChannelException
     */
    public function create(): PWMChannel|MultiplePWMChannels
    {
        $this->assertReady();

        $primary = $this->openChannel($this->pwm_chip, $this->channel, $this->name ?? 'scrapyard-io-pwm');

        if (count($this->addl_channels) === 0) {
            return $primary;
        }

        $channels = [
            ($this->name ?? 'scrapyard-io-pwm') => $primary,
        ];

        foreach ($this->addl_channels as $name => $offset) {
            $channel_offset = (int) $offset;
            $key = is_string($name) ? $name : (string) $channel_offset;
            $channels[$key] = $this->openChannel($this->pwm_chip, $channel_offset, $key);
        }

        return new MultiplePWMChannels($channels);
    }

    /**
     * @throws PWMChannelException
     */
    protected function openChannel(int|string $chip, int $channel, string $consumer): PWMChannel
    {
        return new PWMChannel(
            NativePWMDriver::export($chip, $channel),
            $consumer,
        );
    }
}
