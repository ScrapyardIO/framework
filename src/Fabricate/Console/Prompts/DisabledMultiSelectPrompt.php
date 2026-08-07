<?php

namespace Fabricate\Console\Prompts;

use Closure;
use Fabricate\NutsAndBolts\Collection;
use Laravel\Prompts\MultiSelectPrompt;

class DisabledMultiSelectPrompt extends MultiSelectPrompt
{
    /**
     * Option keys that cannot be selected.
     *
     * @var array<int, int|string>
     */
    public array $disabled = [];

    /**
     * Create a new disabled multi-select prompt.
     *
     * @param  array<int|string, string>|Collection<int|string, string>  $options
     * @param  array<int|string>|Collection<int, int|string>  $default
     * @param  array<int, int|string>|Collection<int, int|string>  $disabled
     */
    public function __construct(
        string $label,
        array|Collection $options,
        array|Collection $default = [],
        int $scroll = 5,
        bool|string $required = false,
        mixed $validate = null,
        string $hint = 'Use the space bar to select options.',
        ?Closure $transform = null,
        string|Closure $info = '',
        array|Collection $disabled = [],
    ) {
        $this->disabled = $disabled instanceof Collection ? $disabled->all() : $disabled;

        $default = $default instanceof Collection ? $default->all() : $default;
        $default = array_values(array_filter(
            $default,
            fn (int|string $value): bool => ! $this->isDisabled($value),
        ));

        parent::__construct(
            $label,
            $options,
            $default,
            $scroll,
            $required,
            $validate,
            $hint,
            $transform,
            $info,
        );
    }

    /**
     * Determine whether the given option value is disabled.
     */
    public function isDisabled(int|string $value): bool
    {
        return in_array($value, $this->disabled, true);
    }

    /**
     * Override the theme renderer used for this prompt.
     */
    protected function getRenderer(): callable
    {
        return new Themes\DisabledMultiSelectPromptRenderer($this);
    }

    /**
     * Toggle all enabled options.
     */
    protected function toggleAll(): void
    {
        $enabled = array_values(array_filter(
            array_is_list($this->options)
                ? array_values($this->options)
                : array_keys($this->options),
            fn (int|string $value): bool => ! $this->isDisabled($value),
        ));

        if (count(array_intersect($this->values, $enabled)) === count($enabled)) {
            $this->values = [];

            return;
        }

        $this->values = $enabled;
    }

    /**
     * Toggle the highlighted entry when it is not disabled.
     */
    protected function toggleHighlighted(): void
    {
        $value = array_is_list($this->options)
            ? $this->options[$this->highlighted]
            : array_keys($this->options)[$this->highlighted];

        if ($this->isDisabled($value)) {
            return;
        }

        if (in_array($value, $this->values, true)) {
            $this->values = array_values(array_filter(
                $this->values,
                fn ($selected) => $selected !== $value,
            ));

            return;
        }

        $this->values[] = $value;
    }
}
