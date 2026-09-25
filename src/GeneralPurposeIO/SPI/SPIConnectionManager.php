<?php

namespace GeneralPurposeIO\SPI;

use GeneralPurposeIO\Contracts\SPI\SPIException;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\WorkTarget;
use Voyager\Contracts\Vessel\DataBindingException;
use Voyager\NutsAndBolts\Manager;

class SPIConnectionManager extends Manager
{
    public function createNoneDriver(): SPIConnectionDriver
    {
        return new NoneSPIConnectionDriver;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('gpio.protocols.spi.default', 'none');
    }

    /** Every driver, built in or extend()ed, looks the loop and the work targets up when used, so provider order never matters. */
    protected function createDriver(string $driver): SPIConnectionDriver
    {
        return parent::createDriver($driver)
            ->resolvesLoopWith(fn (): ?Loop => $this->eventLoop())
            ->resolvesTargetsWith(fn (?string $target): WorkTarget => $this->workTarget($target));
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

    private function workTarget(?string $target): WorkTarget
    {
        if (! $this->vessel->isBound('work-targets')) {
            throw SPIException::noWorkTargets();
        }

        return $this->vessel->make('work-targets')->driver($target);
    }
}
