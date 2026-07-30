<?php

namespace Fabricate\Actuation\HumanInput;

use Fabricate\Contracts\Actuation\HumanInput\ButtonHoldThreshold;
use Fabricate\Contracts\Actuation\HumanInput\ButtonInput;
use Fabricate\Contracts\Actuation\Interfaces\Button;

class BasicButton implements Button
{
    protected bool $is_down = false;

    protected bool $pressed_edge = false;

    protected bool $released_edge = false;

    protected ?int $down_since_ns = null;

    /**
     * @var list<array{down: bool, pressed: bool, released: bool, holding: bool, at_ns: int}>
     */
    protected array $history = [];

    public function __construct(
        public readonly string $label,
        protected ButtonInput $button,
        protected int $hold_ms = ButtonHoldThreshold::DEFAULT->value,
        protected int $history_limit = 32,
    ) {}

    public function label(): string
    {
        return $this->label;
    }

    public function input(): ButtonInput
    {
        return $this->button;
    }

    public function holdMs(): int
    {
        return $this->hold_ms;
    }

    public function setHoldMs(int|ButtonHoldThreshold $hold_ms): static
    {
        $this->hold_ms = $hold_ms instanceof ButtonHoldThreshold ? $hold_ms->value : $hold_ms;

        return $this;
    }

    public function poll(): static
    {
        $down = $this->button->isDown();
        $now = hrtime(true);

        $this->pressed_edge = $down && ! $this->is_down;
        $this->released_edge = ! $down && $this->is_down;

        if ($down && is_null($this->down_since_ns)) {
            $this->down_since_ns = $now;
        } elseif (! $down) {
            $this->down_since_ns = null;
        }

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
        return $this->is_down
            && ! is_null($this->down_since_ns)
            && $this->heldMs() >= $this->hold_ms;
    }

    public function heldMs(): int
    {
        if (! $this->is_down || is_null($this->down_since_ns)) {
            return 0;
        }

        return (int) ((hrtime(true) - $this->down_since_ns) / 1_000_000);
    }

    public function history(): array
    {
        return $this->history;
    }

    public function clearHistory(): static
    {
        $this->history = [];

        return $this;
    }

    public function close(): void
    {
        $this->button->close();
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
