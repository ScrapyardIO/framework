<?php

namespace Fabricate\Contracts\UX\Enums;

/**
 * How a flex container distributes leftover space along its main axis.
 */
enum MainAxisAlignment: string
{
    case START = 'START';
    case CENTER = 'CENTER';
    case END = 'END';
    case SPACE_BETWEEN = 'SPACE_BETWEEN';
    case SPACE_AROUND = 'SPACE_AROUND';
    case SPACE_EVENLY = 'SPACE_EVENLY';

    /**
     * True when the free space goes between the children rather than around the
     * group as a whole.
     */
    public function distributesBetweenChildren(): bool
    {
        return match ($this) {
            self::SPACE_BETWEEN, self::SPACE_AROUND, self::SPACE_EVENLY => true,
            self::START, self::CENTER, self::END => false,
        };
    }
}
