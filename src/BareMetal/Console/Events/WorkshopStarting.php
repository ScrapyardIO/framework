<?php

namespace BareMetal\Console\Events;

use BareMetal\Console\Machine;

class WorkshopStarting
{
    /**
     * Create a new event instance.
     *
     * @param  Machine  $workshop  The Workshop console application instance.
     */
    public function __construct(
        public Machine $workshop,
    ) {
    }
}
