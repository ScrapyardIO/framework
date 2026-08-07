<?php

namespace Fabricate\Console\Events;

use Fabricate\Console\ConsoleProgram;
use Fabricate\Console\WorkshopInstance;
use Symfony\Component\Console\Application as SymfonyApplication;

class WorkshopStarting
{
    public function __construct(
        public WorkshopInstance|ConsoleProgram|SymfonyApplication $workshop
    ) {}
}
