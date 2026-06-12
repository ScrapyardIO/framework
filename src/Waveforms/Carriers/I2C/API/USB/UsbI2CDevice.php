<?php

namespace Waveforms\Carriers\I2C\API\USB;

use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;
use Waveforms\Carriers\GPIO\Contracts\SharesMpsseContext;
use Waveforms\Carriers\I2C\I2CDevice;

class UsbI2CDevice extends I2CDevice implements SharesMpsseContext
{
    public function __construct(
        protected MPSSEContext $context,
        protected int $address,
    ) {}

    public function mpsseContext(): MPSSEContext
    {
        return $this->context;
    }

    public function read(int $len): array|false
    {
        MPSSE::start($this->context);

        if (! $this->writeByte(($this->address << 1) | 1)) {
            MPSSE::stop($this->context);

            return false;
        }

        $data = $this->clockIn($len);

        MPSSE::stop($this->context);

        return is_null($data) ? false : bytes2array($data);
    }

    public function write(string|array $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        MPSSE::start($this->context);

        $acknowledged = $this->writeByte(($this->address << 1) | 0)
            && $this->clockOut($data);

        MPSSE::stop($this->context);

        return $acknowledged ? strlen($data) : -1;
    }

    public function readWrite(string|array $bytes_to_write, int $bytes_to_read): array|false
    {
        if (is_array($bytes_to_write)) {
            $bytes_to_write = array2bytes($bytes_to_write);
        }

        MPSSE::start($this->context);

        if (! $this->writeByte(($this->address << 1) | 0) || ! $this->clockOut($bytes_to_write)) {
            MPSSE::stop($this->context);

            return false;
        }

        MPSSE::start($this->context);

        if (! $this->writeByte(($this->address << 1) | 1)) {
            MPSSE::stop($this->context);

            return false;
        }

        $data = $this->clockIn($bytes_to_read);

        MPSSE::stop($this->context);

        return is_null($data) ? false : bytes2array($data);
    }

    public function close(): void
    {
        mpsse_close($this->context);
    }

    /**
     * Clock out a single byte and confirm the slave acknowledged it.
     */
    private function writeByte(int $byte): bool
    {
        if (MPSSE::write($this->context, chr($byte & 0xFF)) !== 0) {
            return false;
        }

        return MPSSE::getAck($this->context) === 0;
    }

    /**
     * Clock out a payload one byte at a time so every ACK is validated.
     */
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

    /**
     * Clock in the requested number of bytes, master-ACKing every byte except
     * the final one, which is NACKed to signal the slave to release the bus.
     */
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
