<?php

namespace Fabricate\Actuation\Components;

use Closure;
use Fabricate\Actuation\ActuationComponent;
use Fabricate\Contracts\Actuation\ActuationException;
use Fabricate\Contracts\Actuation\HumanInput\InputPad as InputPadContract;
use Fabricate\IntegratedCircuits\ActuatorIC;

abstract class InputPad extends ActuationComponent implements InputPadContract
{
    /** @var array<string, BasicInput> */
    protected array $buttons = [];

    /**
     * @param  iterable<BasicInput>  $button_layout
     * @param  ?Closure(): void  $before_poll
     */
    public function __construct(
        iterable $button_layout,
        protected ?Closure $before_poll = null,
    ) {
        foreach ($button_layout as $button) {
            if (! $button instanceof BasicInput) {
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
     * @return array<string, BasicInput>
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

    public function button(string $label): BasicInput
    {
        if (! isset($this->buttons[$label])) {
            throw ActuationException::buttonNotFound($label, static::class);
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

    /** @return list<string> */
    public function downLabels(): array
    {
        return $this->labelsWhere(fn (BasicInput $button): bool => $button->isDown());
    }

    /** @return list<string> */
    public function pressedLabels(): array
    {
        return $this->labelsWhere(fn (BasicInput $button): bool => $button->isPressed());
    }

    /** @return list<string> */
    public function holdingLabels(): array
    {
        return $this->labelsWhere(fn (BasicInput $button): bool => $button->isHolding());
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

    public static function buildWith(ActuatorIC $actuator): static
    {
        throw new ActuationException(static::class.' is composed from BasicInput instances, not a single ActuatorIC.');
    }

    /** @param  list<string>  $labels @return list<string> */
    protected function resolveLabels(array $labels): array
    {
        return $labels === [] ? $this->labels() : $labels;
    }

    /** @param  callable(BasicInput): bool  $predicate @return list<string> */
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
