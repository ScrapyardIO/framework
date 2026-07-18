<?php

namespace GeneralPurposeIO\PWM;

class MultiplePWMChannels
{
    /**
     * @param  array<string, PWMChannel>  $channels
     */
    public function __construct(
        public readonly array $channels,
    ) {}

    public function getChannel(string $name): ?PWMChannel
    {
        return $this->channels[$name] ?? null;
    }

    public function close(): void
    {
        foreach ($this->channels as $channel) {
            if ($channel instanceof PWMChannel) {
                $channel->close();
            }
        }
    }
}
