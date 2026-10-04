<?php

namespace GeneralPurposeIO\Digital;

use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Contracts\Digital\LineBias;

/** The driver an app gets when no adapter package is configured: every open attempt says so. */
class NoneDigitalIOConnectionDriver extends DigitalIOConnectionDriver
{
    protected function newConnection(int|string $device): DigitalIOConnectionFactory
    {
        throw DigitalIOException::noDriverConfigured();
    }

    protected function getOutputTransport(string|int $device, int $pin): DigitalOutputTransport
    {
        throw DigitalIOException::noDriverConfigured();
    }

    protected function getInputTransport(string|int $device, int $pin, LineBias $bias = LineBias::AS_IS, bool $active_low = false): DigitalInputTransport
    {
        throw DigitalIOException::noDriverConfigured();
    }

    /** newConnection() never succeeds, so there is never a handle to close. */
    protected function closeConnection(mixed $handle): void {}
}
