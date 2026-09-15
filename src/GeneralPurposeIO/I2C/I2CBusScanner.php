<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\I2C\Drivers\I2CDriver;

class I2CBusScanner
{
    public function __construct(
        protected I2CDriver $driver,
        protected ?int $posixBusNumber = null,
        protected int $first = 0x03,
        protected int $last = 0x77,
    ) {}

    /**
     * Scan the bus and return an i2cdetect-style grid string.
     */
    public function render(): string
    {
        $lines = [];
        $lines[] = '     0  1  2  3  4  5  6  7  8  9  a  b  c  d  e  f';

        for ($row = 0x00; $row <= 0x70; $row += 0x10) {
            $cells = [];

            for ($col = 0x00; $col <= 0x0F; $col++) {
                $address = $row | $col;
                $cells[] = $this->cellFor($address);
            }

            $lines[] = sprintf('%02x: %s', $row, implode('', $cells));
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    protected function cellFor(int $address): string
    {
        if ($address < $this->first || $address > $this->last) {
            return '   ';
        }

        if ($this->isKernelBound($address)) {
            return 'UU ';
        }

        if ($this->driver->probe($address)) {
            return sprintf('%02x ', $address);
        }

        return '-- ';
    }

    /**
     * Mirror i2cdetect's UU cells for addresses claimed by a kernel driver.
     */
    protected function isKernelBound(int $address): bool
    {
        if (is_null($this->posixBusNumber)) {
            return false;
        }

        $path = sprintf(
            '/sys/bus/i2c/devices/%d-%04x/driver',
            $this->posixBusNumber,
            $address,
        );

        return file_exists($path) || is_link($path);
    }
}
