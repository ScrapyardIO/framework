<?php

namespace Fabricate\Console\View\Components\Mutators;

class EnsureDynamicContentIsHighlighted
{
    /**
     * Highlight dynamic content within the given string.
     *
     * @param string $string
     * @return string
     */
    public function __invoke(string $string): string
    {
        return preg_replace('/\[([^\]]+)\]/', '<options=bold>[$1]</>', (string) $string);
    }
}
