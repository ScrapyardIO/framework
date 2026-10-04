<?php

namespace GeneralPurposeIO\UART;

use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\Vessel\DataBindingException;
use Voyager\NutsAndBolts\Manager;

class UARTConnectionManager extends Manager
{
    public function createNoneDriver(): UARTConnectionDriver
    {
        return new NoneUARTConnectionDriver;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('gpio.protocols.uart.default', 'none');
    }

    /** Every driver, built in or extend()ed, looks the loop up when used, so provider order never matters. */
    protected function createDriver(string $driver): UARTConnectionDriver
    {
        return parent::createDriver($driver)->resolvesLoopWith(fn (): ?Loop => $this->eventLoop());
    }

    private function eventLoop(): ?Loop
    {
        if (! $this->vessel->isBound(Loop::class)) {
            return null;
        }

        try {
            return $this->vessel->make(Loop::class);
        } catch (DataBindingException) {
            // the core alias can mark the loop bound before IOPools registers a concrete one
            return null;
        }
    }
}
