<?php

namespace Fabricate\Console\Events;

use Fabricate\Console\ConsoleProgram;

class WorkshopStarting
{
    public function __construct(
        public ConsoleProgram $workshop
    ){}

}
