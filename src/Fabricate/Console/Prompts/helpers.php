<?php

namespace Fabricate\Console\Prompts;

use Closure;
use Illuminate\Support\Collection;

if (! function_exists('Fabricate\Console\Prompts\disabled_multiselect')) {
    /**
     * Prompt the user to select multiple options, keeping some options visible but unselectable.
     *
     * @param  array<int|string, string>|Collection<int|string, string>  $options
     * @param  array<int|string>|Collection<int, int|string>  $default
     * @param  array<int, int|string>|Collection<int, int|string>  $disabled
     * @return array<int|string>
     */
    function disabled_multiselect(
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
    ): array {
        return (new DisabledMultiSelectPrompt(...get_defined_vars()))->prompt();
    }
}
