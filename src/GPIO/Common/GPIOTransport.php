<?php

namespace GPIO\Common;

use GPIO\Contracts\Common\GeneralPurposeIO;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;

abstract class GPIOTransport implements GeneralPurposeIO
{
    public function __construct(
        protected readonly GPIODriverAdapterInterface $driver,
        protected readonly GPIOConnectionHandleInterface $handle,
    ) {}

    abstract protected function driver(): GPIODriverAdapterInterface;
    abstract protected function handle(): GPIOConnectionHandleInterface;

    public function close(): void
    {
        $this->driver()->close($this->handle());
    }
}
