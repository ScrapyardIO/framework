<?php

namespace Waveforms\Carriers\I2C\API\Native;

use Microscrap\Bindings\I2C\DataObjects\I2CBus;
use Microscrap\Bindings\I2C\Enums\I2CMsgFlag;
use Waveforms\Carriers\I2C\I2CDevice;

class NativeI2CDevice extends I2CDevice
{
    public function __construct(
        protected I2CBus $bus
    ) {}

    public function read(int $len): array|false
    {
        $bytes = i2c_read($this->bus, $len);

        if ($bytes === false) {
            return false;
        }

        return bytes2array($bytes);
    }

    public function write(string|array $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        return i2c_write($this->bus, $data);
    }

    public function readWrite(string|array $bytes_to_write, int $bytes_to_read): array|false
    {
        if (is_array($bytes_to_write)) {
            $bytes_to_write = array2bytes($bytes_to_write);
        }

        $read = i2c_rdwr($this->bus, [
            ['flags' => 0, 'data' => $bytes_to_write],
            ['flags' => I2CMsgFlag::M_RD->value, 'len' => $bytes_to_read],
        ]);

        if ($read === false) {
            return false;
        }

        return bytes2array($read);
    }

    public function close(): void
    {
        i2c_close($this->bus);
    }
}
