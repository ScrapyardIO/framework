<?php

namespace Fabricate\Console\Prompts\Themes;

use Fabricate\Console\Prompts\DisabledMultiSelectPrompt;
use Laravel\Prompts\Themes\Default\MultiSelectPromptRenderer;

class DisabledMultiSelectPromptRenderer extends MultiSelectPromptRenderer
{
    /**
     * Render the multiselect prompt.
     */
    public function __invoke(DisabledMultiSelectPrompt|\Laravel\Prompts\MultiSelectPrompt $prompt): string
    {
        return parent::__invoke($prompt);
    }

    /**
     * Render the options, dimming disabled entries and blocking selection affordances.
     */
    protected function renderOptions(\Laravel\Prompts\MultiSelectPrompt $prompt): string
    {
        if (! $prompt instanceof DisabledMultiSelectPrompt) {
            return parent::renderOptions($prompt);
        }

        return implode(PHP_EOL, $this->scrollbar(
            array_values(array_map(function ($label, $key) use ($prompt) {
                $label = $this->truncate($label, $prompt->terminal()->cols() - 12);

                $index = array_search($key, array_keys($prompt->options), true);
                $active = $index === $prompt->highlighted;

                $value = array_is_list($prompt->options)
                    ? $prompt->options[$index]
                    : array_keys($prompt->options)[$index];

                $selected = in_array($value, $prompt->value(), true);
                $disabled = $prompt->isDisabled($value);

                if ($disabled) {
                    return match (true) {
                        $active => "{$this->dim('›')} {$this->dim('◌')} {$this->dim($label)}  ",
                        default => "  {$this->dim('◌')} {$this->dim($label)}  ",
                    };
                }

                if ($prompt->state === 'cancel') {
                    return $this->dim(match (true) {
                        $active && $selected => "› ◼ {$this->strikethrough($label)}  ",
                        $active => "› ◻ {$this->strikethrough($label)}  ",
                        $selected => "  ◼ {$this->strikethrough($label)}  ",
                        default => "  ◻ {$this->strikethrough($label)}  ",
                    });
                }

                return match (true) {
                    $active && $selected => "{$this->cyan('› ◼')} {$label}  ",
                    $active => "{$this->cyan('›')} ◻ {$label}  ",
                    $selected => "  {$this->cyan('◼')} {$this->dim($label)}  ",
                    default => "  {$this->dim('◻')} {$this->dim($label)}  ",
                };
            }, $visible = $prompt->visible(), array_keys($visible))),
            $prompt->firstVisible,
            $prompt->scroll,
            count($prompt->options),
            min($this->longest($prompt->options, padding: 6), $prompt->terminal()->cols() - 6),
            $prompt->state === 'cancel' ? 'dim' : 'cyan'
        ));
    }
}
