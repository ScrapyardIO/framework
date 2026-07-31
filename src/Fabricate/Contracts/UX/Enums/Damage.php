<?php

namespace Fabricate\Contracts\UX\Enums;

/**
 * What kind of work a change requires.
 *
 * The distinction is the difference between a cheap frame and an expensive one.
 * A ProgressBar value change, or a numeric readout whose remeasured size is
 * unchanged, only needs repainting. Only a real size change forces a relayout,
 * and relayout stops climbing at the first fixed-size ancestor.
 */
enum Damage: string
{
    case PAINT = 'PAINT';
    case LAYOUT = 'LAYOUT';

    /**
     * Layout implies paint, so the wider of two damages wins when they merge.
     */
    public function merge(self $other): self
    {
        return ($this === self::LAYOUT) || ($other === self::LAYOUT)
            ? self::LAYOUT
            : self::PAINT;
    }

    public function requiresLayout(): bool
    {
        return $this === self::LAYOUT;
    }
}
