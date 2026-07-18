<?php

namespace GeneralPurposeIO\I2C\Drivers;

use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;

class UsbI2CDriver extends I2CDriver
{
    public function __construct(
        public readonly MPSSEContext $context,
        public readonly int $slave_address,
    ) {}

    public function close(): void
    {
        mpsse_close($this->context);
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        if (is_array($bytes_to_write)) {
            $bytes_to_write = array2bytes($bytes_to_write);
        }

        MPSSE::start($this->context);

        $wrote = $this->writeByte(($this->slave_address << 1) | 0)
            && $this->clockOut($bytes_to_write);

        if (! $wrote) {
            MPSSE::stop($this->context);

            return false;
        }

        MPSSE::start($this->context); // repeated START

        if (! $this->writeByte(($this->slave_address << 1) | 1)) {
            MPSSE::stop($this->context);

            return false;
        }

        $data = $this->clockIn($bytes_to_read);

        MPSSE::stop($this->context);

        return is_null($data) ? false : bytes2array($data);
    }

    public function bulkWrite(array|string $messages): array|false
    {
        $chunks = static::normalizeBulkMessages($messages);
        if (count($chunks) === 0) {
            return [];
        }

        MPSSE::start($this->context);

        $acknowledged = $this->writeByte(($this->slave_address << 1) | 0);
        foreach ($chunks as $chunk) {
            $acknowledged = $acknowledged && $this->clockOut($chunk);
        }

        MPSSE::stop($this->context);

        return $acknowledged ? array_map('strlen', $chunks) : false;
    }

    public function read(int $len): array|false
    {
        MPSSE::start($this->context);

        if (! $this->writeByte(($this->slave_address << 1) | 1)) {
            MPSSE::stop($this->context);

            return false;
        }

        $data = $this->clockIn($len);

        MPSSE::stop($this->context);

        return is_null($data) ? false : bytes2array($data);
    }

    public function write(array|string $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        MPSSE::start($this->context);

        $acknowledged = $this->writeByte(($this->slave_address << 1) | 0)
            && $this->clockOut($data);

        MPSSE::stop($this->context);

        return $acknowledged ? strlen($data) : -1;
    }

    private function writeByte(int $byte): bool
    {
        if (MPSSE::write($this->context, chr($byte & 0xFF)) !== 0) {
            return false;
        }

        return MPSSE::getAck($this->context) === 0;
    }

    private function clockOut(string $data): bool
    {
        $len = strlen($data);

        for ($i = 0; $i < $len; $i++) {
            if (! $this->writeByte(ord($data[$i]))) {
                return false;
            }
        }

        return true;
    }

    private function clockIn(int $len): ?string
    {
        if ($len <= 0) {
            return '';
        }

        $data = '';

        if ($len > 1) {
            MPSSE::sendAcks($this->context);
            $chunk = MPSSE::read($this->context, $len - 1);

            if (is_null($chunk)) {
                return null;
            }

            $data .= $chunk;
        }

        MPSSE::sendNacks($this->context);
        $last = MPSSE::read($this->context, 1);

        if (is_null($last)) {
            return null;
        }

        return $data.$last;
    }
}
