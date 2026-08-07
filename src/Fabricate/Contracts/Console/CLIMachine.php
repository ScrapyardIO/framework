<?php

namespace Fabricate\Contracts\Console;

use Closure;

interface CLIMachine
{
    public static function starting(Closure $callback): void;
}