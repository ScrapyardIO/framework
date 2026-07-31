<?php

namespace Fabricate\Contracts\Actuation\Interfaces;

interface LEDGrid extends LEDShape
{
    public function columns(): int;

    public function rows(): int;

    public function indexAt(int $x, int $y): int;
}
