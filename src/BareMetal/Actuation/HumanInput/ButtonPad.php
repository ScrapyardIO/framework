<?php

namespace BareMetal\Actuation\HumanInput;

use BareMetal\Actuation\ActuationComponent;
use BareMetal\Contracts\Actuators\ActuationException;
use BareMetal\Contracts\Actuators\HumanInput\ButtonPad as ButtonPadContract;
use Closure;

abstract class ButtonPad extends ActuationComponent implements ButtonPadContract
{
    /** @var array<string, BasicButton> */
    protected array $buttons = [];

    /**
     * @param  iterable<BasicButton>  $button_layout
     * @param  ?Closure(): void  $before_poll  Shared pre-poll (e.g. one I2C read for a bank).
     */
    public function __construct(
        iterable $button_layout,
        protected ?Closure $before_poll = null,
    ) {
        foreach ($button_layout as $button) {
            if (! $button instanceof BasicButton) {
                throw ActuationException::invalidButtonLayout(static::class);
            }

            if (isset($this->buttons[$button->label])) {
                throw ActuationException::duplicateButtonLabel($button->label, static::class);
            }

            $this->buttons[$button->label] = $button;
        }
    }

    public function poll(): static
    {
        if (! is_null($this->before_poll)) {
            ($this->before_poll)();
        }

        foreach ($this->buttons as $button) {
            $button->poll();
        }

        return $this;
    }

    /**
     * @return array<string, BasicButton>
     */
    public function buttons(): array
    {
        return $this->buttons;
    }

    /**
     * @return list<string>
     */
    public function labels(): array
    {
        return array_keys($this->buttons);
    }

    public function button(string $label): BasicButton
    {
        if (! isset($this->buttons[$label])) {
            throw ActuationException::buttonNotFound($label, static::class);
        }

        /** @var BasicButton */
        return $this->buttons[$label];
    }

    public function has(string $label): bool
    {
        return isset($this->buttons[$label]);
    }

    public function isDown(string $label): bool
    {
        return $this->button($label)->isDown();
    }

    public function isPressed(string $label): bool
    {
        return $this->button($label)->isPressed();
    }

    public function wasReleased(string $label): bool
    {
        return $this->button($label)->wasReleased();
    }

    public function isHolding(string $label): bool
    {
        return $this->button($label)->isHolding();
    }

    /**
     * @return list<string>
     */
    public function downLabels(): array
    {
        return $this->labelsWhere(fn (BasicButton $button): bool => $button->isDown());
    }

    /**
     * @return list<string>
     */
    public function pressedLabels(): array
    {
        return $this->labelsWhere(fn (BasicButton $button): bool => $button->isPressed());
    }

    /**
     * @return list<string>
     */
    public function holdingLabels(): array
    {
        return $this->labelsWhere(fn (BasicButton $button): bool => $button->isHolding());
    }

    public function anyDown(string ...$labels): bool
    {
        foreach ($this->resolveLabels($labels) as $label) {
            if ($this->isDown($label)) {
                return true;
            }
        }

        return false;
    }

    public function allDown(string ...$labels): bool
    {
        $resolved = $this->resolveLabels($labels);

        if ($resolved === []) {
            return false;
        }

        foreach ($resolved as $label) {
            if (! $this->isDown($label)) {
                return false;
            }
        }

        return true;
    }

    public function chord(string ...$labels): bool
    {
        return $this->allDown(...$labels);
    }

    public function anyPressed(string ...$labels): bool
    {
        foreach ($this->resolveLabels($labels) as $label) {
            if ($this->isPressed($label)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $labels
     * @return list<string>
     */
    protected function resolveLabels(array $labels): array
    {
        return $labels === [] ? $this->labels() : $labels;
    }

    /**
     * @param  callable(BasicButton): bool  $predicate
     * @return list<string>
     */
    protected function labelsWhere(callable $predicate): array
    {
        $matched = [];

        foreach ($this->buttons as $label => $button) {
            if ($predicate($button)) {
                $matched[] = $label;
            }
        }

        return $matched;
    }
}
