<?php

namespace GeneralPurposeIO\Contracts\UART;

use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

interface UARTTransport extends GPIOTransport
{
    public function path(): string;

    /** Discards the bytes the port and this transport hold unread, and what is queued to send. */
    public function flush(): void;

    /** Up to $length bytes: unread ones at once, else the first to arrive within $timeout_ms (-1: no limit, 0: no wait). [] on timeout. */
    public function read(int $length, int $timeout_ms = -1): array;

    /** Everything up to and including the first $delimiter, waiting up to $timeout_ms. Null on timeout: the bytes stay unread. */
    public function readUntil(string $delimiter, int $timeout_ms = -1): ?string;

    /** Hands every byte of $data to the OS (the tty queue, or libusb) within $timeout_ms, or throws. */
    public function write(array|string $data, int $timeout_ms = -1): int;

    /** Up to $max_bytes of what has arrived; never waits. */
    public function pollBytes(int $max_bytes = 4096): string;

    /** How many unread bytes were dropped because the buffer was full. */
    public function dropped(): int;

    /** Asserts or releases the port's DTR line. */
    public function dtr(bool $asserted): void;

    /** Asserts or releases the port's RTS line. */
    public function rts(bool $asserted): void;

    /** Puts the port on the loop and mails each chunk it receives as UARTReceived; the bytes stay readable. */
    public function watch(): void;

    /** Stops the mail; the port leaves the loop unless a call is waiting on it. */
    public function unwatch(): void;
}
