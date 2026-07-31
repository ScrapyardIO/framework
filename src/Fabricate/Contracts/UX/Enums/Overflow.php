<?php

namespace Fabricate\Contracts\UX\Enums;

/**
 * What happens to a child that does not fit its parent.
 *
 * CLIP is the safe default because the renderer clip already enforces it for
 * free. VISIBLE deliberately allows painting beyond the bounds, which is how a
 * focus ring or a badge escapes its container.
 */
enum Overflow: string
{
    case CLIP = 'CLIP';
    case VISIBLE = 'VISIBLE';

    public function clips(): bool
    {
        return $this === self::CLIP;
    }
}
