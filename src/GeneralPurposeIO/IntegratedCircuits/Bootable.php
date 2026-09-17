<?php

namespace GeneralPurposeIO\IntegratedCircuits;

use Exception;
use GeneralPurposeIO\Contracts\IntegratedCircuits\BootScaffolding;
use GeneralPurposeIO\Contracts\IntegratedCircuits\BootSequence;

abstract class Bootable extends IntegratedCircuit implements BootSequence
{
    use BootScaffolding;

    /**
     * @throws Exception
     */
    public function __construct(
        bool $boot_now,
    ) {
        if($boot_now) {
            $this->boot();
        }
    }
}