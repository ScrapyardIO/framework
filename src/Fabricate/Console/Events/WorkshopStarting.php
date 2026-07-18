<?php

namespace Fabricate\Console\Events;

use Fabricate\Console\ConsoleMachine;

class WorkshopStarting
{
    public function __construct(
        public ConsoleMachine $workshop
    ){}

}
