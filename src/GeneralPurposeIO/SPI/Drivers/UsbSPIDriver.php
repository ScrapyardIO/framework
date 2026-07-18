<?php

namespace GeneralPurposeIO\SPI\Drivers;

use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;

class UsbSPIDriver extends SPIDriver
{
    public function __construct(
        public readonly MPSSEContext $context,
    ) {}

    public function close(): void
    {
        mpsse_close($this->context);
    }

    public function read(int $len): array|false
    {
        MPSSE::start($this->context);
        $rx = MPSSE::read($this->context, $len);
        MPSSE::stop($this->context);

        return is_null($rx) ? false : bytes2array($rx);
    }

    public function write(array|string $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        MPSSE::start($this->context);
        $result = MPSSE::write($this->context, $data);
        MPSSE::stop($this->context);

        return $result === 0 ? strlen($data) : -1;
    }

    public function transfer(array|string $data): array|false
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        MPSSE::start($this->context);
        $rx = MPSSE::transfer($this->context, $data);
        MPSSE::stop($this->context);

        return is_null($rx) ? false : bytes2array($rx);
    }
}
