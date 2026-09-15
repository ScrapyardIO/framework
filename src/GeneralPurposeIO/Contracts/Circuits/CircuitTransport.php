<?php

namespace GeneralPurposeIO\Contracts\Circuits;

/**
 * Protocol / bus channels referenced by #[IntegratedCircuit] and #[Pinout].
 */
enum CircuitTransport: string
{
    case SPI = 'SPI';
    case I2C = 'I2C';
    case UART = 'UART';
    case DIGITAL_IO = 'DigitalIO';
    case GPIO = 'GPIO';
    case PWM = 'PWM';

    public static function tryFromLabel(string $label): ?self
    {
        $normalized = strtolower(str_replace(['_', '-'], '', $label));

        return match ($normalized) {
            'spi' => self::SPI,
            'i2c' => self::I2C,
            'uart' => self::UART,
            'digitalio', 'digital' => self::DIGITAL_IO,
            'gpio' => self::GPIO,
            'pwm' => self::PWM,
            default => null,
        };
    }

    public function adapterParam(): string
    {
        return match ($this) {
            self::SPI => 'spi_adapter',
            self::DIGITAL_IO => 'digital_adapter',
            default => 'adapter',
        };
    }

    public function deviceParam(): string
    {
        return match ($this) {
            self::SPI => 'spi_device',
            self::DIGITAL_IO => 'digital_device',
            default => 'device',
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}
