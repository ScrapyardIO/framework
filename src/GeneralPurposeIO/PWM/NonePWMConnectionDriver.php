<?php

namespace GeneralPurposeIO\PWM;

use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\Contracts\PWM\PWMTransport;

/** The driver an app gets when no adapter package is configured: every open attempt says so. */
class NonePWMConnectionDriver extends PWMConnectionDriver
{
    protected function newConnection(int|string $device): PWMConnectionFactory
    {
        throw PWMException::noDriverConfigured();
    }

    protected function getTransport(string|int $device, int $channel): PWMTransport
    {
        throw PWMException::noDriverConfigured();
    }
}
