<?php

namespace GeneralPurposeIO\SPI;

use GeneralPurposeIO\Contracts\SPI\SPITransport as TransportContract;

abstract class SPITransport implements TransportContract
{
    public function __construct(
        public readonly int $chip_select
    ) {}

    abstract public function handle(): mixed;

}