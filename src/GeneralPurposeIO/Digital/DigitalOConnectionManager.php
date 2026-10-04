<?php

namespace GeneralPurposeIO\Digital;

use Voyager\NutsAndBolts\Manager;
use Voyager\Contracts\Vessel\DataBindingException;
use Voyager\Contracts\IOPools\Loop as LoopInterface;

class DigitalOConnectionManager extends Manager
{
    public function createNoneDriver(): DigitalIOConnectionDriver
    {
        return new NoneDigitalIOConnectionDriver();
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('gpio.protocols.digital-in.default', 'none');
    }

    /**
     * Every driver, built in or extend()ed, looks the loop up at call time, so provider order never matters.
     */
    protected function createDriver(string $driver): DigitalIOConnectionDriver
    {
        return parent::createDriver($driver)->resolvesLoopWith(fn (): ?LoopInterface => $this->eventLoop());
    }

    private function eventLoop(): ?LoopInterface
    {
        if (! $this->vessel->isBound(LoopInterface::class)) {
            return null;
        }

        try {
            return $this->vessel->make(LoopInterface::class);
        } catch (DataBindingException) {
            // the core alias can mark the loop bound before IOPools registers a concrete one
            return null;
        }
    }
}