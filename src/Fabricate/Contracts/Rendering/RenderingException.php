<?php

namespace Fabricate\Contracts\Rendering;

use RuntimeException;

class RenderingException extends RuntimeException
{
    /**
     * @param  array<int, string>  $installed  The registered renderer names
     */
    public static function rendererNotInstalled(string $renderer, array $installed): static
    {
        $available = ($installed === []) ? 'none' : implode(', ', $installed);

        return new static("The '{$renderer}' rendering library is not installed. Installed renderers: {$available}.");
    }

    public static function framebufferNotAttached(string $renderer): static
    {
        return new static("{$renderer} has no framebuffer attached — call useFramebuffer() before drawing.");
    }
}
