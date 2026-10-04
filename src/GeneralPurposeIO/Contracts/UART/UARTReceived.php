<?php

namespace GeneralPurposeIO\Contracts\UART;

use Voyager\Contracts\Signals\NamedSignal;

/**
 * One chunk of bytes from one port. Mailed as gpio.uart.<device>; crosses a worker or Redis wire through
 * toData()/fromData(), with the bytes base64-encoded so a JSON wire carries any byte.
 */
class UARTReceived implements NamedSignal
{
    /**
     * @param int $timestamp CLOCK_MONOTONIC ns when the bytes were collected
     * @param int $seqno per port, from 1: a gap is a lost chunk
     */
    public function __construct(
        public readonly string $device,
        public readonly string $bytes,
        public readonly int $timestamp,
        public readonly int $seqno,
    ) {}

    public function name(): string
    {
        return "gpio.uart.{$this->device}";
    }

    public function uuid(): string
    {
        return "{$this->name()}.{$this->timestamp}.{$this->seqno}";
    }

    public function toData(): array
    {
        return [...get_object_vars($this), 'bytes' => base64_encode($this->bytes)];
    }

    public static function fromData(array $data): static
    {
        return new static(...[...$data, 'bytes' => base64_decode($data['bytes'])]);
    }
}
