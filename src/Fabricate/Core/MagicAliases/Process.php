<?php

namespace Fabricate\Core\MagicAliases;

use Closure;
use Fabricate\MagicAliases\MagicAlias;
use Fabricate\Process\Factory;

/**
 * @method static \Fabricate\Process\PendingProcess command(array|string $command)
 * @method static \Fabricate\Process\PendingProcess path(string $path)
 * @method static \Fabricate\Process\PendingProcess timeout(\Carbon\CarbonInterval|int $timeout)
 * @method static \Fabricate\Process\PendingProcess idleTimeout(\Carbon\CarbonInterval|int $timeout)
 * @method static \Fabricate\Process\PendingProcess forever()
 * @method static \Fabricate\Process\PendingProcess env(array $environment)
 * @method static \Fabricate\Process\PendingProcess input(\Traversable|resource|string|int|float|bool|null $input)
 * @method static \Fabricate\Process\PendingProcess quietly()
 * @method static \Fabricate\Process\PendingProcess tty(bool $tty = true)
 * @method static \Fabricate\Process\PendingProcess options(array $options)
 * @method static \Fabricate\Contracts\Process\ProcessResult run(array|string|null $command = null, callable|null $output = null)
 * @method static \Fabricate\Process\InvokedProcess start(array|string|null $command = null, callable|null $output = null)
 * @method static bool supportsTty()
 * @method static \Fabricate\Process\FakeProcessResult result(array|string $output = '', array|string $errorOutput = '', int $exitCode = 0)
 * @method static \Fabricate\Process\FakeProcessDescription describe()
 * @method static \Fabricate\Process\FakeProcessSequence sequence(array $processes = [])
 * @method static bool isRecording()
 * @method static \Fabricate\Process\Factory preventStrayProcesses(bool $prevent = true)
 * @method static bool preventingStrayProcesses()
 * @method static \Fabricate\Process\Factory assertRan(\Closure|string $callback)
 * @method static \Fabricate\Process\Factory assertRanTimes(\Closure|string $callback, int $times = 1)
 * @method static \Fabricate\Process\Factory assertNotRan(\Closure|string $callback)
 * @method static \Fabricate\Process\Factory assertDidntRun(\Closure|string $callback)
 * @method static \Fabricate\Process\Factory assertNothingRan()
 * @method static \Fabricate\Process\Pool pool(callable $callback)
 * @method static \Fabricate\Contracts\Process\ProcessResult pipe(callable|array $callback, callable|null $output = null)
 * @method static \Fabricate\Process\ProcessPoolResults concurrently(callable $callback, callable|null $output = null)
 * @method static \Fabricate\Process\PendingProcess newPendingProcess()
 *
 * @see \Fabricate\Process\Factory
 */
class Process extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'process';
    }

    /**
     * Indicate that the process factory should fake processes.
     *
     * @param  \Closure|array|null  $callback
     * @return \Fabricate\Process\Factory
     */
    public static function fake(Closure|array|null $callback = null): Factory
    {
        return tap(static::getMagicAliasRoot(), function ($fake) use ($callback) {
            static::swap($fake->fake($callback));
        });
    }
}
