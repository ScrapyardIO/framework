<?php

namespace GeneralPurposeIO\Core\Extensions;

/**
 * The PHP extensions scrapyard:ext installs through PIE. Each value is the token `pie install` takes: the Packagist
 * package and its version line.
 */
enum ScrapyardExtension: string
{
    case POSI = 'php-io-extensions/posi:^0.10';
    case FTDI = 'php-io-extensions/ftdi:^0.10';

    /** The case PHP loads under this name, or null. */
    public static function named(string $extension): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->extension() === strtolower($extension)) {
                return $case;
            }
        }

        return null;
    }

    /** The name PHP loads it under. */
    public function extension(): string
    {
        return strtolower($this->name);
    }

    /** Whether a loaded version is on the ^0.10 line this package and the microscrap packages require. */
    public static function accepts(string $version): bool
    {
        return version_compare($version, '0.10.0', '>=') && version_compare($version, '0.11.0', '<');
    }

    public function description(): string
    {
        return match ($this) {
            self::POSI => 'POSIX calls for GPIO character devices, I2C, SPI and serial ports',
            self::FTDI => 'FTDI USB bridges such as the FT232H, through libftdi',
        };
    }

    /**
     * Why this operating system cannot have the extension, or null when it can. Both manifests exclude Windows.
     *
     * @param  string  $os_family  A PHP_OS_FAMILY value.
     */
    public function unsupportedOn(string $os_family): ?string
    {
        return $os_family === 'Windows' ? 'not on Windows' : null;
    }
}
