<?php

namespace GeneralPurposeIO\PWM\Drivers;

use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\Contracts\PWM\PWMPolarity;

class NativePWMDriver extends PWMDriver
{
    public readonly string $chip_path;

    public function __construct(
        public readonly int $chip,
    ) {
        $this->chip_path = "/sys/class/pwm/pwmchip{$this->chip}";
    }

    public function close(int $channel): void
    {
        $channel_path = "{$this->chip_path}/pwm{$channel}";
        @file_put_contents("{$channel_path}/enable", '0');
        @file_put_contents("{$this->chip_path}/unexport", (string) $channel);
    }

    public function setDutyCycle(int $channel, int $value): int
    {
        $this->writeAttribute($channel, 'duty_cycle', (string) $value);

        return $this->getDutyCycle($channel);
    }

    /**
     * @throws PWMException
     */
    public function getDutyCycle(int $channel): int
    {
        return (int) $this->readAttribute($channel, 'duty_cycle');
    }

    /**
     * @throws PWMException
     */
    public function setPeriod(int $channel, int $value): int
    {
        $this->writeAttribute($channel, 'period', (string) $value);

        return $this->getPeriod($channel);
    }

    /**
     * @throws PWMException
     */
    public function getPeriod(int $channel): int
    {
        return (int) $this->readAttribute($channel, 'period');
    }

    /**
     * @throws PWMException
     */
    public function setEnable(int $channel, bool $value): bool
    {
        $this->writeAttribute($channel, 'enable', $value ? '1' : '0');

        return $this->getEnable($channel);
    }

    /**
     * @throws PWMException
     */
    public function getEnable(int $channel,): bool
    {
        return $this->readAttribute($channel, 'enable') === '1';
    }

    /**
     * @throws PWMException
     */
    public function setPolarity(int $channel, PWMPolarity $value): PWMPolarity
    {
        $this->writeAttribute($channel, 'polarity', $value->value);

        return $this->getPolarity($channel);
    }

    /**
     * @throws PWMException
     */
    public function getPolarity(int $channel): PWMPolarity
    {
        return PWMPolarity::from($this->readAttribute($channel, 'polarity'));
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
     * @throws PWMException
     */
    protected function writeAttribute(int $channel, string $attribute, string $value): void
    {
        $channel_path = "{$this->chip_path}/pwm{$channel}";
        $path = "{$channel_path}/{$attribute}";
        if (@file_put_contents($path, $value) === false) {
            throw PWMException::couldNotWrite($path);
        }
    }

    /**
     * @throws PWMException
     */
    protected function readAttribute(int $channel, string $attribute): string
    {
        $channel_path = "{$this->chip_path}/pwm{$channel}";
        $path = "{$channel_path}/{$attribute}";
        $value = @file_get_contents($path);
        if ($value === false) {
            throw PWMException::couldNotRead($path);
        }

        return trim($value);
    }

    /**
     * @throws PWMException
     */
    public function waitUntilWritable(string $path, int $timeout_ms = 500): void
    {
        $deadline = hrtime(true) + ($timeout_ms * 1_000_000);

        do {
            if (is_writable($path)) {
                return;
            }

            usleep(10_000);
        } while (hrtime(true) < $deadline);

        throw PWMException::channelNotReady($path);
    }
}