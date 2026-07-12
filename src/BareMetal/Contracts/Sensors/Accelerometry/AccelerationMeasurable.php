<?php

namespace BareMetal\Contracts\Sensors\Accelerometry;

use BareMetal\Contracts\Sensors\MeasuresData;

interface AccelerationMeasurable extends MeasuresData
{
    public function x(): float;
    public function y(): float;
    public function z(): float;
}
