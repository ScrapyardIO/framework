<?php

namespace GeneralPurposeIO\I2C\Drivers;

use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Common\Concerns\Splices16Bits;

class UsbI2CDriver extends I2CDriver
{
    use Splices16Bits;

    public function __construct(
        protected readonly MPSSEContext $context,
    ) {}

    public function probe(int $address): bool
    {
        if ($address < 0x03 || $address > 0x77) {
            return false;
        }

        MPSSE::start($this->context);
        $acknowledged = $this->writeByte(($address << 1) | 0);
        MPSSE::stop($this->context);

        return $acknowledged;
    }

    public function writeRead(int $address, array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        if (is_array($bytes_to_write)) {
            $bytes_to_write = array2bytes($bytes_to_write);
        }

        MPSSE::start($this->context);

        $wrote = $this->writeByte(($address << 1) | 0)
            && $this->clockOut($bytes_to_write);

        if (! $wrote) {
            MPSSE::stop($this->context);

            return false;
        }

        MPSSE::start($this->context); // repeated START

        if (! $this->writeByte(($address << 1) | 1)) {
            MPSSE::stop($this->context);

            return false;
        }

        $data = $this->clockIn($bytes_to_read);

        MPSSE::stop($this->context);

        return is_null($data) ? false : bytes2array($data);
    }

    public function bulkWrite(int $address, array|string $messages): array|false
    {
        $chunks = static::normalizeBulkMessages($messages);
        if (count($chunks) === 0) {
            return [];
        }

        MPSSE::start($this->context);

        $acknowledged = $this->writeByte(($address << 1) | 0);
        foreach ($chunks as $chunk) {
            $acknowledged = $acknowledged && $this->clockOut($chunk);
        }

        MPSSE::stop($this->context);

        return $acknowledged ? array_map('strlen', $chunks) : false;
    }

    /**
     * @throws I2CException
     */
    public function read(int $address, int $len): array|false
    {
        if (!(($address > 0x07) && ($address <= 0x77))) {
            throw I2CException::invalidSlaveAddress($address);
        }

        MPSSE::start($this->context);

        if (! $this->writeByte(($address << 1) | 1)) {
            MPSSE::stop($this->context);

            return false;
        }

        $data = $this->clockIn($len);

        MPSSE::stop($this->context);

        return is_null($data) ? false : bytes2array($data);
    }

    public function write(int $address, array|string $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        MPSSE::start($this->context);

        $acknowledged = $this->writeByte(($address << 1) | 0)
            && $this->clockOut($data);

        MPSSE::stop($this->context);

        return $acknowledged ? strlen($data) : -1;
    }

    public function close(): void
    {
        mpsse_close($this->context);
    }

    public function getContext(): MPSSEContext
    {
        return $this->context;
    }

    private function writeByte(int $byte): bool
    {
        if (MPSSE::write($this->context, chr($this->getLowByte($byte))) !== 0) {
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