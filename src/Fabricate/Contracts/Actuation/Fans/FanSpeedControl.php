<?php

namespace Fabricate\Contracts\Actuation\Fans;

interface FanSpeedControl extends BasicFanFunctionality
{
    public function speed(?int $percent = null): int;

    public function frequency(?int $hz = null): int;
}
