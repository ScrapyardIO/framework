<?php

namespace Fabricate\Contracts\Sensors;

interface AccelerationMeasurements
{
    public function x(): float;
    public function y(): float;
    public function z(): float;
}
