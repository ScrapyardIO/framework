<?php

namespace BareMetal\Actuation\HumanInput;

use BareMetal\Actuation\ActuationComponent;
use BareMetal\Actuation\HumanInput\Enums\ButtonHoldThreshold;
use BareMetal\Contracts\Actuators\HumanInput\BasicButtonFunctionality as BasicButtonInterface;
use BareMetal\Contracts\Actuators\HumanInput\ButtonComponent as ButtonComponentContract;

class BasicButton extends ActuationComponent implements ButtonComponentContract
{
    protected bool $is_down = false;

    protected bool $was_down = false;

    protected bool $pressed_edge = false;

    protected bool $released_edge = false;

    protected ?int $down_since_ns = null;

    /**
     * @var list<array{down: bool, pressed: bool, released: bool, holding: bool, at_ns: int}>
     */
    protected array $history = [];

    public function __construct(
        public readonly string $label,
        protected BasicButtonInterface $button,
        protected int $hold_ms = ButtonHoldThreshold::DEFAULT->value,
        protected int $history_limit = 32,
    ) {}

    public function label(): string
    {
        return $this->label;
    }

    public function button(): BasicButtonInterface
    {
        return $this->button;
    }

    public function holdMs(): int
    {
        return $this->hold_ms;
    }

    public function setHoldMs(int|ButtonHoldThreshold $hold_ms): static
    {
        $this->hold_ms = $hold_ms instanceof ButtonHoldThreshold
            ? $hold_ms->value
            : $hold_ms;

        return $this;
    }

    public function poll(): static
    {
        $down = $this->button->isDown();
        $now = hrtime(true);
        $previously_down = $this->is_down;

        $this->pressed_edge = $down && ! $previously_down;
        $this->released_edge = ! $down && $previously_down;

        if ($down) {
            if (is_null($this->down_since_ns)) {
                $this->down_since_ns = $now;
            }
        } else {
            $this->down_since_ns = null;
        }

        $this->was_down = $previously_down;
        $this->is_down = $down;

        $this->recordHistory($now);

        return $this;
    }

    public function isDown(): bool
    {
        return $this->is_down;
    }

    public function isPressed(): bool
    {
        return $this->pressed_edge;
    }

    public function wasReleased(): bool
    {
        return $this->released_edge;
    }

    public function isHolding(): bool
    {
        if (! $this->is_down || is_null($this->down_since_ns)) {
            return false;
        }

        return $this->heldMs() >= $this->hold_ms;
    }

    public function heldMs(): int
    {
        if (! $this->is_down || is_null($this->down_since_ns)) {
            return 0;
        }

        return (int) ((hrtime(true) - $this->down_since_ns) / 1_000_000);
    }

    /**
     * @return list<array{down: bool, pressed: bool, released: bool, holding: bool, at_ns: int}>
     */
    public function history(): array
    {
        return $this->history;
    }

    public function clearHistory(): static
    {
        $this->history = [];

        return $this;
    }

    protected function recordHistory(int $at_ns): void
    {
        $this->history[] = [
            'down' => $this->is_down,
            'pressed' => $this->pressed_edge,
            'released' => $this->released_edge,
            'holding' => $this->isHolding(),
            'at_ns' => $at_ns,
        ];

        if (count($this->history) > $this->history_limit) {
            $this->history = array_slice($this->history, -$this->history_limit);
        }
    }
}
