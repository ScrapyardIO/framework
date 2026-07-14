<?php

namespace Illuminate\Contracts\Broadcasting;

interface BroadcastingFactory
{
    /**
     * Get a broadcaster implementation by name.
     */
    public function connection(?string $name = null): Broadcaster;
}
