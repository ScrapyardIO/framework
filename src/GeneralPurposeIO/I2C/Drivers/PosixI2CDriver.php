<?php

namespace GeneralPurposeIO\I2C\Drivers;

use Microscrap\Bindings\I2C\DataObjects\I2CBus;
use Microscrap\Bindings\I2C\Enums\I2CMsgFlag;

class PosixI2CDriver extends I2CDriver
{
    public function __construct(
        public readonly I2CBus $bus,
        public readonly int $slave_address,
    ) {}

    public function close(): void
    {
        i2c_close($this->bus);
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        $write_bytes = is_array($bytes_to_write) ? array2bytes($bytes_to_write) : $bytes_to_write;

        $result = i2c_rdwr($this->bus, [
            ['flags' => 0, 'data' => $write_bytes],
            ['flags' => I2CMsgFlag::M_RD->value, 'len' => $bytes_to_read],
        ]);

        if ($result === false) {
            return false;
        }

        return bytes2array($result);
    }

    public function bulkWrite(array|string $messages): array|false
    {
        $chunks = static::normalizeBulkMessages($messages);
        if (count($chunks) === 0) {
            return [];
        }

        $result = i2c_rdwr($this->bus, array_map(
            static fn (string $chunk): array => ['flags' => 0, 'data' => $chunk],
            $chunks,
        ));

        if ($result === false) {
            return false;
        }

        return array_map('strlen', $chunks);
    }

    public function read(int $len): array|false
    {
        $bytes = i2c_read($this->bus, $len);

        if ($bytes === false) {
            return false;
        }

        return bytes2array($bytes);
    }

    public function write(array|string $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        return i2c_write($this->bus, $data);
    }
}
