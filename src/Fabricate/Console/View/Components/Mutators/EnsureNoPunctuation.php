<?php

namespace Fabricate\Console\View\Components\Mutators;

use Fabricate\NutsAndBolts\Stringable;

class EnsureNoPunctuation
{
    /**
     * Ensures the given string does not end with punctuation.
     *
     * @param string $string
     * @return string
     */
    public function __invoke(string $string): string
    {
        if (new Stringable($string)->endsWith(['.', '?', '!', ':'])) {
            return substr_replace($string, '', -1);
        }

        return $string;
    }
}
