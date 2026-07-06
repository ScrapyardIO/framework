<?php

namespace BareMetal\Sensors\Accelerometry;

use BareMetal\Contracts\Sensors\Accelerometry\AccelerometerEvent;
use BareMetal\Contracts\Sensors\Accelerometry\AxisOrientation;

class MotionEvent extends AccelerometerEvent
{
    public function __construct(
        public readonly ?float $x,
        public readonly ?float $y,
        public readonly ?float $z,
        public readonly ?float $pitch,
        public readonly ?float $roll,
        public readonly ?float $inclination,
        public readonly ?AxisOrientation $orientation,
        float|int $timestamp
    )
    {
        parent::__construct($timestamp);
    }
}
