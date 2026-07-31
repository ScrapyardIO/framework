<?php

namespace Fabricate\Contracts\UX\Enums;

/**
 * Which way focus moves through the traversal order.
 *
 * One dimension, not four: traversal order is tree order, so a d-pad's four
 * directions collapse onto forwards and backwards rather than pretending a
 * spatial navigation model exists.
 */
enum FocusDirection: string
{
    case NEXT = 'NEXT';
    case PREVIOUS = 'PREVIOUS';

    public function step(): int
    {
        return ($this === self::NEXT) ? 1 : -1;
    }

    /**
     * Where focus lands when nothing holds it yet: the first node going
     * forwards, the last going backwards.
     */
    public function entryIndex(int $count): int
    {
        return ($this === self::NEXT) ? 0 : ($count - 1);
    }
}
