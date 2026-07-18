<?php

namespace Fabricate\Contracts\Queue;

interface Monitor
{
    public function size(?string $queue = null): int;
}
