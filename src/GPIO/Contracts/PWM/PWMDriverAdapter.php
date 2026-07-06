<?php

namespace GPIO\Contracts\PWM;

use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterContract;
use GPIO\Contracts\PWM\PWMConnectionHandle as PWMConnectionHandleInterface;

interface PWMDriverAdapter extends GPIODriverAdapterContract
{
    public function buildConnection(
        int|string $chip,
        int $channel,
        string $consumer,
        array $addl_channels = []
    ): PWMChannelTransport|PWMChannelBus;

    public function setDutyCycle(int $value, PWMConnectionHandleInterface $handle): int;
    public function getDutyCycle(PWMConnectionHandleInterface $handle): int;

    public function setPeriod(int $value, PWMConnectionHandleInterface $handle): int;
    public function getPeriod(PWMConnectionHandleInterface $handle): int;

    public function setEnable(bool $value, PWMConnectionHandleInterface $handle): bool;
    public function getEnable(PWMConnectionHandleInterface $handle): bool;

    public function setPolarity(string $value, PWMConnectionHandleInterface $handle): string;
    public function getPolarity(PWMConnectionHandleInterface $handle): string;
}
