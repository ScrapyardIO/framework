<?php

namespace Fabricate\Console\Scheduling;

/**
 * Cron day-of-week values used by schedule frequency helpers.
 */
enum DayOfWeek: int
{
    case SUNDAY = 0;
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;
}
