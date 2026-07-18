<?php

namespace GeneralPurposeIO\PWM\Drivers;

use GeneralPurposeIO\Contracts\PWM\PWMChannelException;
use GeneralPurposeIO\Contracts\PWM\PWMPolarity;

class NativePWMDriver extends PWMDriver
{
    public readonly string $chip_path;

    public readonly string $path;

    public readonly int|string $chip;

    public function __construct(
        int|string $chip,
        public readonly int $channel,
    ) {
        $this->chip = self::resolveChip($chip);
        $this->chip_path = "/sys/class/pwm/pwmchip{$this->chip}";
        $this->path = "{$this->chip_path}/pwm{$this->channel}";
    }

    public function close(): void
    {
        @file_put_contents("{$this->path}/enable", '0');
        @file_put_contents("{$this->chip_path}/unexport", (string) $this->channel);
    }

    public function setDutyCycle(int $value): int
    {
        $this->writeAttribute('duty_cycle', (string) $value);

        return $this->getDutyCycle();
    }

    public function getDutyCycle(): int
    {
        return (int) $this->readAttribute('duty_cycle');
    }

    public function setPeriod(int $value): int
    {
        $this->writeAttribute('period', (string) $value);

        return $this->getPeriod();
    }

    public function getPeriod(): int
    {
        return (int) $this->readAttribute('period');
    }

    public function setEnable(bool $value): bool
    {
        $this->writeAttribute('enable', $value ? '1' : '0');

        return $this->getEnable();
    }

    public function getEnable(): bool
    {
        return $this->readAttribute('enable') === '1';
    }

    public function setPolarity(PWMPolarity $value): PWMPolarity
    {
        $this->writeAttribute('polarity', $value->value);

        return $this->getPolarity();
    }

    public function getPolarity(): PWMPolarity
    {
        return PWMPolarity::from($this->readAttribute('polarity'));
    }

    /**
     * @throws PWMChannelException
     */
    public static function export(int|string $chip, int $channel): self
    {
        $driver = new self($chip, $channel);

        if (! is_dir($driver->chip_path)) {
            throw PWMChannelException::chipNotFound($driver->chip);
        }

        if (! is_dir($driver->path)) {
            $written = @file_put_contents("{$driver->chip_path}/export", (string) $channel);
            if ($written === false) {
                throw PWMChannelException::couldNotExport($driver->chip, $channel);
            }
        }

        // Kernel creates the channel dir before udev chmods attribute files.
        $driver->waitUntilWritable("{$driver->path}/period");

        return $driver;
    }

    public static function resolveChip(int|string $chip): int|string
    {
        if (is_int($chip)) {
            return $chip;
        }

        if (preg_match('/pwmchip(\d+)\s*$/', $chip, $matches) === 1) {
            return (int) $matches[1];
        }

        if (is_numeric($chip)) {
            return (int) $chip;
        }

        return $chip;
    }

    /**
     * @throws PWMChannelException
     */
    protected function waitUntilWritable(string $path, int $timeout_ms = 500): void
    {
        $deadline = hrtime(true) + ($timeout_ms * 1_000_000);

        do {
            if (is_writable($path)) {
                return;
            }

            usleep(10_000);
        } while (hrtime(true) < $deadline);

        throw PWMChannelException::channelNotReady($path);
    }

    /**
     * @throws PWMChannelException
     */
    protected function writeAttribute(string $attribute, string $value): void
    {
        $path = "{$this->path}/{$attribute}";
        if (@file_put_contents($path, $value) === false) {
            throw PWMChannelException::couldNotWrite($path);
        }
    }

    /**
     * @throws PWMChannelException
     */
    protected function readAttribute(string $attribute): string
    {
        $path = "{$this->path}/{$attribute}";
        $value = @file_get_contents($path);
        if ($value === false) {
            throw PWMChannelException::couldNotRead($path);
        }

        return trim($value);
    }
}
