<?php

namespace Fabricate\NutsAndBolts;

use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use DateTimeZone;
use Fabricate\NutsAndBolts\Defer\DeferredCallback;
use Fabricate\NutsAndBolts\Defer\DeferredCallbackCollection;
use Fabricate\NutsAndBolts\MagicAliases\Date;
use Symfony\Component\Process\PhpExecutableFinder;
use UnitEnum;
use function Fabricate\NutsAndBolts\enum_value;

if (! function_exists('Fabricate\NutsAndBolts\defer')) {
    /**
     * Defer execution of the given callback.
     *
     * @param  callable|null  $callback
     * @param  string|null  $name
     * @param  bool  $always
     * @return ($callback is null ? DeferredCallbackCollection : \Fabricate\NutsAndBolts\Defer\DeferredCallback)
     */
    function defer(?callable $callback = null, ?string $name = null, bool $always = false): DeferredCallback|DeferredCallbackCollection
    {
        if ($callback === null) {
            return app(DeferredCallbackCollection::class);
        }

        return tap(
            new DeferredCallback($callback, $name, $always),
            fn ($deferred) => app(DeferredCallbackCollection::class)[] = $deferred
        );
    }
}

if (! function_exists('Fabricate\NutsAndBolts\php_binary')) {
    /**
     * Determine the PHP Binary.
     */
    function php_binary(): string
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}

if (! function_exists('Fabricate\NutsAndBolts\workshop_binary')) {
    /**
     * Determine the proper Workshop executable.
     */
    function workshop_binary(): string
    {
        return defined('WORKSHOP_BINARY') ? WORKSHOP_BINARY : 'workshop';
    }
}

// Time functions...

if (! function_exists('Fabricate\NutsAndBolts\now')) {
    /**
     * Create a new Carbon instance for the current time.
     *
     * @param UnitEnum|DateTimeZone|string|null $tz
     * @return Carbon
     */
    function now(UnitEnum|DateTimeZone|string|null $tz = null): CarbonInterface
    {
        return Date::now(enum_value($tz));
    }
}

if (! function_exists('Fabricate\NutsAndBolts\microseconds')) {
    /**
     * Get the current date / time plus the given number of microseconds.
     */
    function microseconds(int|float $microseconds): CarbonInterval
    {
        return CarbonInterval::microseconds($microseconds);
    }
}

if (! function_exists('Fabricate\NutsAndBolts\milliseconds')) {
    /**
     * Get the current date / time plus the given number of milliseconds.
     */
    function milliseconds(int|float $milliseconds): CarbonInterval
    {
        return CarbonInterval::milliseconds($milliseconds);
    }
}

if (! function_exists('Fabricate\NutsAndBolts\seconds')) {
    /**
     * Get the current date / time plus the given number of seconds.
     */
    function seconds(int|float $seconds): CarbonInterval
    {
        return CarbonInterval::seconds($seconds);
    }
}

if (! function_exists('Fabricate\NutsAndBolts\minutes')) {
    /**
     * Get the current date / time plus the given number of minutes.
     */
    function minutes(int|float $minutes): CarbonInterval
    {
        return CarbonInterval::minutes($minutes);
    }
}

if (! function_exists('Fabricate\NutsAndBolts\hours')) {
    /**
     * Get the current date / time plus the given number of hours.
     */
    function hours(int|float $hours): CarbonInterval
    {
        return CarbonInterval::hours($hours);
    }
}

if (! function_exists('Fabricate\NutsAndBolts\days')) {
    /**
     * Get the current date / time plus the given number of days.
     */
    function days(int|float $days): CarbonInterval
    {
        return CarbonInterval::days($days);
    }
}

if (! function_exists('Fabricate\NutsAndBolts\weeks')) {
    /**
     * Get the current date / time plus the given number of weeks.
     */
    function weeks(int $weeks): CarbonInterval
    {
        return CarbonInterval::weeks($weeks);
    }
}

if (! function_exists('Fabricate\NutsAndBolts\months')) {
    /**
     * Get the current date / time plus the given number of months.
     */
    function months(int $months): CarbonInterval
    {
        return CarbonInterval::months($months);
    }
}

if (! function_exists('Fabricate\NutsAndBolts\years')) {
    /**
     * Get the current date / time plus the given number of years.
     */
    function years(int $years): CarbonInterval
    {
        return CarbonInterval::years($years);
    }
}

