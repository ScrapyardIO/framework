<?php

namespace Fabricate\Contracts\UX\Enums;

/**
 * How much of the offered main axis a flex container claims for itself.
 *
 * MAX is the default and the usual want: a column given a screen takes the
 * screen, and its main-axis alignment then has room to distribute. MIN is for a
 * container that has to be exactly as long as its children — a card in a column,
 * a toolbar in a row — because a MAX child swallows the whole remaining axis and
 * squeezes every sibling after it to nothing.
 *
 * Neither is a guarantee. A tight offer wins over both, which is why the root of
 * a tree fills the surface whichever this says.
 */
enum MainAxisSize: string
{
    case MAX = 'MAX';

    case MIN = 'MIN';

    /**
     * Whether a bounded main axis should be filled rather than wrapped.
     */
    public function fillsAvailable(): bool
    {
        return $this === self::MAX;
    }
}
