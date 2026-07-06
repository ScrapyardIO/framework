<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\UART;

use GPIO\Common\CarrierDriverManager;
use GPIO\Contracts\UART\UARTDriverAdapter as UARTDriverAdapterInterface;

/**
 * A fake carrier whose uart driver records exactly what it was asked to
 * build, so UARTConnectionFactory can be unit-tested without any real
 * hardware carrier.
 */
class FakeUARTCarrierDriverManager extends CarrierDriverManager
{
    public function __construct()
    {
        parent::__construct('fake-uart');
    }

    protected function createUARTDriver(): UARTDriverAdapterInterface
    {
        return new FakeUARTDriverAdapter;
    }
}
