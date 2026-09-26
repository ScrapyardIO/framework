<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\PWM\PWMTransport;

/**
 * A channel whose attributes live on a static board shared by every driver in the process, so a worker-side driver
 * sees what the caller's wrote. Like the kernel, it refuses a duty cycle longer than the period.
 */
final class FakePWMTransport extends PWMTransport
{
    /** @var array<string, array<string, int|bool>> "<board>:<chip>:<channel>" => attributes */
    public static array $boards = [];

    /** @var list<string> every write, "<board>:<chip>:<channel>:<attribute>=<value>", oldest first */
    public static array $log = [];

    public bool $released = false;

    public function __construct(
        int $channel,
        public readonly string $board,
        public readonly FakePWMHandle $chip,
    ) {
        parent::__construct($channel);
    }

    public function handle(): FakePWMHandle
    {
        return $this->chip;
    }

    public function getPeriod(): int
    {
        return $this->read('period');
    }

    public function setPeriod(int $value): int
    {
        $this->write('period', $value);

        return $this->getPeriod();
    }

    public function getEnable(): bool
    {
        return $this->read('enable');
    }

    public function setEnable(bool $value): bool
    {
        $this->write('enable', $value);

        return $this->getEnable();
    }

    public function getDutyCycle(): int
    {
        return $this->read('duty_cycle');
    }

    public function setDutyCycle(int $value): int
    {
        $this->write('duty_cycle', $value);

        return $this->getDutyCycle();
    }

    public function getPolarity(): bool
    {
        return $this->read('polarity');
    }

    public function setPolarity(bool $value): bool
    {
        $this->write('polarity', $value);

        return $this->getPolarity();
    }

    protected function release(): void
    {
        $this->released = true;
    }

    private function read(string $attribute): int|bool
    {
        $this->ensureOpen();
        $this->awaitTurn();

        return $this->attributes()[$attribute];
    }

    private function write(string $attribute, int|bool $value): void
    {
        $this->ensureOpen();
        $this->awaitTurn();

        if ($attribute === 'duty_cycle' && $value > $this->attributes()['period']) {
            throw PWMException::couldNotWrite("{$this->key()}/duty_cycle");
        }

        self::$boards[$this->key()] = [...$this->attributes(), $attribute => $value];
        self::$log[] = "{$this->key()}:{$attribute}=".var_export($value, true);
    }

    /** Looked up on every call: a test resets the boards while a worker-side driver keeps its transports. */
    private function attributes(): array
    {
        return self::$boards[$this->key()] ??= self::blankAttributes();
    }

    /** @return array{period: int, enable: bool, duty_cycle: int, polarity: bool} */
    private static function blankAttributes(): array
    {
        return ['period' => 0, 'enable' => false, 'duty_cycle' => 0, 'polarity' => false];
    }

    private function key(): string
    {
        return "{$this->board}:{$this->chip->device}:{$this->channel}";
    }
}
