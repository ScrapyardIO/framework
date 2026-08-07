<?php

namespace Fabricate\Console\Scheduling;

enum ScheduleSignalKey: string
{
    case PAUSED = 'fabricate:schedule:paused';
    case INTERRUPT = 'fabricate:schedule:interrupt';
}
