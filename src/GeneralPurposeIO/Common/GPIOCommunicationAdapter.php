<?php

namespace GeneralPurposeIO\Common;

use GeneralPurposeIO\Contracts\Common\GPIOCommunicationAdapter as AdapterContract;
use GeneralPurposeIO\Contracts\Common\GPIOException;

abstract class GPIOCommunicationAdapter implements AdapterContract
{
    /**
     * @throws GPIOException
     */
    public function __construct() {
        $this->confirmDependencies();
    }

    /**
     * @throws GPIOException
     */
    abstract protected function confirmDependencies(): void;
}