<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\Contracts\Console\CLIKernel as ConsoleKernelContract;
use Fabricate\MagicAliases\MagicAlias;

/**
 * @method static int handle(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface|null $output = null)
 * @method static void terminate(\Symfony\Component\Console\Input\InputInterface $input, int $status)
 * @method static void whenCommandLifecycleIsLongerThan(\DateTimeInterface|\Carbon\CarbonInterval|float|int $threshold, callable $handler)
 * @method static \Fabricate\NutsAndBolts\Carbon|null commandStartedAt()
 * @method static \Fabricate\Console\Scheduling\Schedule resolveConsoleSchedule()
 * @method static \Fabricate\Core\Console\ClosureCommand command(string $signature, \Closure $callback)
 * @method static void registerCommand(\Symfony\Component\Console\Command\Command $command)
 * @method static int call(\Symfony\Component\Console\Command\Command|string $command, array $parameters = [], \Symfony\Component\Console\Output\OutputInterface|null $outputBuffer = null)
 * @method static array all()
 * @method static string output()
 * @method static void bootstrap()
 * @method static void bootstrapWithoutBootingProviders()
 * @method static void setWorkshop(\Fabricate\Console\WorkshopInstance|null $workshop)
 * @method static \Fabricate\Core\Console\ConsoleKernel addCommands(array $commands)
 * @method static \Fabricate\Core\Console\ConsoleKernel addCommandPaths(array $paths)
 * @method static \Fabricate\Core\Console\ConsoleKernel addCommandRoutePaths(array $paths)
 *
 * @see \Fabricate\Core\Console\ConsoleKernel
 */
class Workshop extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return ConsoleKernelContract::class;
    }
}
