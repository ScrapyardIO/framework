<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

use Closure;

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
 * @method static \Fabricate\Process\PendingProcess withFakeHandlers(array $fakeHandlers)
 * @method static \Fabricate\Process\PendingProcess|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Fabricate\Process\PendingProcess|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Fabricate\Process\FakeProcessResult result(array|string $output = '', array|string $errorOutput = '', int $exitCode = 0)
 * @method static \Fabricate\Process\FakeProcessDescription describe()
 * @method static \Fabricate\Process\FakeProcessSequence sequence(array $processes = [])
 * @method static bool isRecording()
 * @method static \Fabricate\Process\Factory recordIfRecording(\Fabricate\Process\PendingProcess $process, \Fabricate\Contracts\Process\ProcessResult $result)
 * @method static \Fabricate\Process\Factory record(\Fabricate\Process\PendingProcess $process, \Fabricate\Contracts\Process\ProcessResult $result)
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
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 *
 * @see \Fabricate\Process\PendingProcess
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
    public static function fake(Closure|array|null $callback = null)
    {
        return tap(static::getMagicAliasRoot(), function ($fake) use ($callback) {
            static::swap($fake->fake($callback));
        });
    }
}
