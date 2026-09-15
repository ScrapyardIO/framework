<?php

namespace GeneralPurposeIO\I2C\Drivers;

use GeneralPurposeIO\Contracts\I2C\I2CDriver as DriverContract;

abstract class I2CDriver implements DriverContract
{
    abstract public function close(): void;

    /**
     * SMBus-quick-style presence probe (like i2cdetect -q).
     */
    abstract public function probe(int $address): bool;

    abstract public function read(int $address,int $len): array|false;

    abstract public function write(int $address,array|string $data): int;

    abstract public function writeRead(int $address, array|string $bytes_to_write, int $bytes_to_read): array|false;

    abstract public function bulkWrite(int $address,array|string $messages): array|false;

    protected static function normalizeBulkMessages(array|string $messages): array
    {
        if (is_string($messages)) {
            return [$messages];
        }

        if ($messages === []) {
            return [];
        }

        $is_single_message = array_reduce(
            $messages,
            static fn (bool $carry, mixed $byte): bool => $carry && is_int($byte),
            true,
        );

        $chunks = $is_single_message ? [$messages] : $messages;

        return array_map(
            static fn (array|string $chunk): string => is_array($chunk) ? array2bytes($chunk) : $chunk,
            $chunks,
        );
    }
}