<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;

/**
 * Counts how often it is actually measured, so the layout memo can be asserted
 * as work not done rather than as a flag.
 */
class MeasureCountingNode extends FilledNode
{
    public int $measure_count = 0;

    public function measure(Constraints $constraints): Size
    {
        $this->measure_count++;

        return parent::measure($constraints);
    }
}
