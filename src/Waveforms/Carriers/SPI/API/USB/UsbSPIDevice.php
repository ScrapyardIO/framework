<?php

namespace Waveforms\Carriers\SPI\API\USB;

use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;
use Waveforms\Carriers\GPIO\Contracts\SharesMpsseContext;
use Waveforms\Carriers\SPI\SPIDevice;

class UsbSPIDevice extends SPIDevice implements SharesMpsseContext
{
    public function __construct(
        protected MPSSEContext $context
    ) {}

    public function mpsseContext(): MPSSEContext
    {
        return $this->context;
    }

    public function transfer(string|array $data): array|false
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        MPSSE::start($this->context);
        $rx = MPSSE::transfer($this->context, $data);
        MPSSE::stop($this->context);

        return is_null($rx) ? false : bytes2array($rx);
    }

    public function read(int $len): array|false
    {
        MPSSE::start($this->context);
        $rx = MPSSE::read($this->context, $len);
        MPSSE::stop($this->context);

        return is_null($rx) ? false : bytes2array($rx);
    }

    public function write(string|array $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        MPSSE::start($this->context);
        $result = MPSSE::write($this->context, $data);
        MPSSE::stop($this->context);

        return $result === 0 ? strlen($data) : -1;
    }

    public function close(): void
    {
        mpsse_close($this->context);
    }
}
