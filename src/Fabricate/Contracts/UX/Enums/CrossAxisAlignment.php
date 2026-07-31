<?php

namespace Fabricate\Contracts\UX\Enums;

/**
 * How a flex container places a child across its cross axis.
 */
enum CrossAxisAlignment: string
{
    case START = 'START';
    case CENTER = 'CENTER';
    case END = 'END';
    case STRETCH = 'STRETCH';

    /**
     * STRETCH is the one case that changes the child's measured size rather than
     * only its position, so layout has to treat it separately.
     */
    public function resizesChild(): bool
    {
        return $this === self::STRETCH;
    }
}
