<?php

namespace Fabricate\UX\Layout;

use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\Contracts\UX\Enums\CrossAxisAlignment;
use Fabricate\Contracts\UX\Enums\MainAxisAlignment;
use Fabricate\Contracts\UX\Enums\MainAxisSize;

/**
 * Children stacked top to bottom.
 */
final class Column extends Flex
{
    public function __construct(
        MainAxisAlignment $main_axis = MainAxisAlignment::START,
        CrossAxisAlignment $cross_axis = CrossAxisAlignment::START,
        int $gap = 0,
        MainAxisSize $main_axis_size = MainAxisSize::MAX,
    ) {
        parent::__construct(Axis::VERTICAL, $main_axis, $cross_axis, $gap, $main_axis_size);
    }
}
