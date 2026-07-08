<?php

namespace BareMetal\Contracts\GFX;

use ScrapyardIO\NutsAndBolts\ScrapyardIOException;

class RendererException extends ScrapyardIOException
{
    /**
     * @param  array<int, string>  $installed  The registered renderer names
     */
    public static function rendererNotInstalled(string $renderer, array $installed): static
    {
        $available = ($installed === []) ? 'none' : implode(', ', $installed);

        return new static("The '{$renderer}' rendering library is not installed. Installed renderers: {$available}.");
    }
}
