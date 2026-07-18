<?php

namespace GeneralPurposeIO\I2C\Drivers;

abstract class I2CDriver
{
    abstract public function close(): void;

    abstract public function read(int $len): array|false;

    abstract public function write(array|string $data): int;

    abstract public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false;

    abstract public function bulkWrite(array|string $messages): array|false;

    /**
     * @return list<string>
     */
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
