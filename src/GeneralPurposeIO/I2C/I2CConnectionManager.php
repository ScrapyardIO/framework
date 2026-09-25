<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Contracts\I2C\I2CException;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\WorkTarget;
use Voyager\Contracts\Vessel\DataBindingException;
use Voyager\NutsAndBolts\Manager;

class I2CConnectionManager extends Manager
{
    public function createNoneDriver(): I2CConnectionDriver
    {
        return new NoneI2CConnectionDriver;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('gpio.protocols.i2c.default', 'none');
    }

    /** Every driver, built in or extend()ed, looks the loop and the work targets up when used, so provider order never matters. */
    protected function createDriver(string $driver): I2CConnectionDriver
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
            throw I2CException::noWorkTargets();
        }

        return $this->vessel->make('work-targets')->driver($target);
    }
}
