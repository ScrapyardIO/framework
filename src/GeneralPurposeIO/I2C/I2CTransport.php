<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\I2C\I2CTransport as TransportContract;

abstract class I2CTransport implements TransportContract
{
    public function __construct(
        public readonly int $address
    ) {
        $this->validateAddress();
    }

    abstract public function handle(): mixed;

    public function address(): int
    {
        return $this->address;
    }

    /**
     * @return void
     * @throws I2CException
     */
    private function validateAddress(): void
    {
        if ($this->address < 0x03 || $this->address > 0x77) {
            throw I2CException::invalidSlaveAddress($this->address);
        }
    }

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