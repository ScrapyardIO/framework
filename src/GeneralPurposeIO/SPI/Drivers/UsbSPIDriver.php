<?php

namespace GeneralPurposeIO\SPI\Drivers;

use GeneralPurposeIO\Digital\DigitalOutputPin;
use GeneralPurposeIO\Digital\Drivers\UsbDigitalIODriver;
use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;

class UsbSPIDriver extends SPIDriver
{
    public function __construct(
        protected readonly MPSSEContext $context,
    ) {}

    /**
     * @param int $chip_select
     * @param int $len
     * @return array|false
     */
    public function read($chip_select, int $len): array|false
    {
        $this->toggleChip($chip_select);
        MPSSE::start($this->context);
        $rx = MPSSE::read($this->context, $len);
        MPSSE::stop($this->context);
        $this->untoggleChip($chip_select);
        return is_null($rx) ? false : bytes2array($rx);
    }

    /**
     * @param int $chip_select
     * @param array|string $data
     * @return int
     */
    public function write($chip_select, array|string $data): int
    {
        $this->toggleChip($chip_select);

        if (is_array($data)) {
            $data = array2bytes($data);
        }

        MPSSE::start($this->context);
        $result = MPSSE::write($this->context, $data);
        MPSSE::stop($this->context);
        $this->untoggleChip($chip_select);
        return $result === 0 ? strlen($data) : -1;
    }

    /**
     * @param int $chip_select
     * @param array|string $data
     * @return array|false
     */
    public function transfer($chip_select, array|string $data): array|false
    {
        $this->toggleChip($chip_select);

        if (is_array($data)) {
            $data = array2bytes($data);
        }

        MPSSE::start($this->context);
        $rx = MPSSE::transfer($this->context, $data);
        MPSSE::stop($this->context);

        $this->untoggleChip($chip_select);
        return is_null($rx) ? false : bytes2array($rx);
    }

    public function close(): void
    {
        mpsse_close($this->context);
    }

    public function getContext(): MPSSEContext
    {
        return $this->context;
    }

    protected function toggleChip(int $chip_select): void
    {
        $driver = new UsbDigitalIODriver($this->getContext());
        $pin = new DigitalOutputPin($chip_select, $driver);
        $pin->low();
    }

    protected function untoggleChip(int $chip_select): void
    {
        $driver = new UsbDigitalIODriver($this->getContext());
        $pin = new DigitalOutputPin($chip_select, $driver);
        $pin->high();
    }
}