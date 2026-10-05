<?php

namespace GeneralPurposeIO\Core\Modules;

use GeneralPurposeIO\Core\Extensions\ScrapyardExtension;

/**
 * The microscrap packages scrapyard:modules installs with Composer. Each value is the Packagist package.
 */
enum MicroscrapModule: string
{
    case GPIO = 'microscrap/gpio';
    case I2C = 'microscrap/i2c';
    case SPI = 'microscrap/spi';
    case UART = 'microscrap/uart';
    case MPSSE = 'microscrap/mpsse';
    case SCRAPYARD_LINUX = 'microscrap/scrapyard-linux';
    case SCRAPYARD_USB = 'microscrap/scrapyard-usb';

    /** The case named by its package's short name (gpio, scrapyard-usb), or null. */
    public static function named(string $module): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->module() === strtolower($module)) {
                return $case;
            }
        }

        return null;
    }

    /** The package's short name. */
    public function module(): string
    {
        return substr($this->value, strlen('microscrap/'));
    }

    /** The token `composer require` takes: the package and its version line. */
    public function requirement(): string
    {
        return "{$this->value}:^0.10";
    }

    /** The PHP extension the package's manifest requires. */
    public function extension(): string
    {
        return match ($this) {
            self::GPIO, self::I2C, self::SPI, self::UART, self::SCRAPYARD_LINUX => 'posi',
            self::MPSSE, self::SCRAPYARD_USB => 'ftdi',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::GPIO => 'libgpiod digital IO over ext-posi',
            self::I2C => 'i2c-dev buses over ext-posi',
            self::SPI => 'spidev buses over ext-posi',
            self::UART => 'serial ports over ext-posi',
            self::MPSSE => 'MPSSE SPI, I2C and GPIO over ext-ftdi',
            self::SCRAPYARD_LINUX => 'the native connection drivers for Linux boards',
            self::SCRAPYARD_USB => 'the usb connection drivers for FTDI bridges',
        };
    }

    /**
     * Why this machine cannot have the package, or null when it can. scrapyard-linux drives Linux-only interfaces
     * (gpiod, i2c-dev, spidev, sysfs PWM); every package needs the extension its manifest requires, on the ^0.10
     * line, which Composer checks against the PHP that runs it.
     *
     * @param  string  $os_family  A PHP_OS_FAMILY value.
     * @param  callable(string): ?string  $version  The version of an extension the running PHP binary loads, or null.
     */
    public function unavailableOn(string $os_family, callable $version): ?string
    {
        $loaded = $version($this->extension());

        return match (true) {
            $this === self::SCRAPYARD_LINUX && $os_family !== 'Linux' => 'Linux only',
            is_null($loaded) => "needs ext-{$this->extension()}",
            ! ScrapyardExtension::accepts($loaded) => "needs ext-{$this->extension()} ^0.10, {$loaded} is loaded",
            default => null,
        };
    }
}
