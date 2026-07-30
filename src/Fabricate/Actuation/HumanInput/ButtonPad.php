<?php

namespace Fabricate\Actuation\HumanInput;

use Closure;
use Fabricate\Contracts\Actuation\ActuatorException;
use Fabricate\Contracts\Actuation\Interfaces\ButtonPad as ButtonPadContract;

abstract class ButtonPad implements ButtonPadContract
{
    /** @var array<string, BasicButton> */
    protected array $buttons = [];

    /**
     * @param  iterable<BasicButton>  $button_layout
     * @param  ?Closure(): void  $before_poll
     */
    public function __construct(
        iterable $button_layout,
        protected ?Closure $before_poll = null,
    ) {
        foreach ($button_layout as $button) {
            if (! $button instanceof BasicButton) {
                throw ActuatorException::invalidButtonLayout(static::class);
            }

            if (isset($this->buttons[$button->label])) {
                throw ActuatorException::duplicateButtonLabel($button->label, static::class);
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

    public function buttons(): array
    {
        return $this->buttons;
    }

    public function labels(): array
    {
        return array_keys($this->buttons);
    }

    public function button(string $label): BasicButton
    {
        if (! isset($this->buttons[$label])) {
            throw ActuatorException::buttonNotFound($label, static::class);
        }

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

    public function downLabels(): array
    {
        return $this->labelsWhere(fn (BasicButton $button): bool => $button->isDown());
    }

    public function pressedLabels(): array
    {
        return $this->labelsWhere(fn (BasicButton $button): bool => $button->isPressed());
    }

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
        $labels = $this->resolveLabels($labels);

        if ($labels === []) {
            return false;
        }

        foreach ($labels as $label) {
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

    public function close(): void
    {
        foreach ($this->buttons as $button) {
            $button->close();
        }
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
