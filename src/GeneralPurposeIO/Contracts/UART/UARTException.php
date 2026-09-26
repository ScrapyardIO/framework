<?php

namespace GeneralPurposeIO\Contracts\UART;

use GeneralPurposeIO\Contracts\Core\GPIOLevelException;

class UARTException extends GPIOLevelException
{
    /** For a failed or timed-out write: how many bytes went out, of how many. */
    public ?int $sent = null;

    public ?int $total = null;

    public static function missingMasterDevice(): static
    {
        return new static('UART port device is missing.');
    }

    public static function couldNotOpenUARTPort(string|int $device): static
    {
        return new static("UART port [{$device}] could not be opened.");
    }

    public static function couldNotOpenFtdiDevice(string $device, string $error): static
    {
        return new static("Could not open FTDI UART device [{$device}]. {$error}");
    }

    public static function couldNotConfigureFtdiDevice(string $device, string $operation, string $error): static
    {
        return new static("Could not configure FTDI UART device [{$device}] during [{$operation}]. {$error}");
    }

    public static function couldNotConfigureUARTPort(string $device): static
    {
        return new static("UART port [{$device}] could not be configured.");
    }

    public static function noDriverConfigured(): static
    {
        return new static('No UART connection driver is configured. Set gpio.protocols.uart.default to an installed adapter.');
    }

    public static function alreadyConnected(string $device): static
    {
        return new static("UART port {$device} is already connected.");
    }

    public static function portClosed(string $device): static
    {
        return new static("UART port {$device} is closed.");
    }

    public static function readFailed(string $device): static
    {
        return new static("Could not read from UART port {$device}.");
    }

    public static function writeFailed(string $device, int $sent, int $total): static
    {
        return static::counted(new static("Could not write to UART port {$device}: {$sent} of {$total} bytes went out."), $sent, $total);
    }

    public static function writeTimedOut(string $device, int $sent, int $total): static
    {
        return static::counted(new static("UART port {$device} took no more bytes before the timeout: {$sent} of {$total} went out."), $sent, $total);
    }

    public static function emptyDelimiter(): static
    {
        return new static('readUntil() needs a delimiter of at least one byte.');
    }

    public static function modemLinesUnsupported(string $device, string $line): static
    {
        return new static("UART port {$device} cannot set {$line}: the device has no modem control lines, or did not answer.");
    }

    public static function modemLineFailed(string $device, string $line): static
    {
        return new static("Could not set {$line} on UART port {$device}.");
    }

    public static function usbWriteFailed(string $device, int $wrote, int $expected): static
    {
        return new static("USB wrote {$wrote} of {$expected} bytes to UART port {$device}.");
    }

    public static function invalidFtdiDevice(string $device): static
    {
        return new static("Invalid FTDI device {$device}.");
    }

    public static function watchNeedsLoop(): static
    {
        return new static('watch() needs an event loop. Boot IOPools, or read() without one.');
    }

    public static function intakeStreamFailed(string $device): static
    {
        return new static("Could not put UART port {$device} on the loop: its fd would not open as a stream.");
    }

    private static function counted(self $e, int $sent, int $total): static
    {
        [$e->sent, $e->total] = [$sent, $total];

        return $e;
    }
}
