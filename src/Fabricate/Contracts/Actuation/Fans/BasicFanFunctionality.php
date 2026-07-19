<?php

namespace Fabricate\Contracts\Actuation\Fans;

interface BasicFanFunctionality
{
    public function on(): void;

    public function off(): void;
}
