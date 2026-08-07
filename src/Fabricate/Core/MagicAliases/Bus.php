<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\MagicAliases\MagicAlias;

/**
 * @method static mixed dispatch(mixed $command)
 * @method static mixed dispatchSync(mixed $command, mixed $handler = null)
 * @method static mixed dispatchNow(mixed $command, mixed $handler = null)
 * @method static bool hasCommandHandler(mixed $command)
 * @method static mixed getCommandHandler(mixed $command)
 * @method static mixed dispatchToQueue(mixed $command)
 * @method static void dispatchAfterResponse(mixed $command, mixed $handler = null)
 * @method static \Fabricate\Bus\Dispatcher pipeThrough(array $pipes)
 * @method static \Fabricate\Bus\Dispatcher map(array $map)
 * @method static mixed findBatch(string $batchId)
 * @method static mixed batch(array|\Fabricate\NutsAndBolts\Collection $jobs)
 * @method static mixed chain(array|\Fabricate\NutsAndBolts\Collection|null $jobs = null)
 *
 * @see \Fabricate\Bus\Dispatcher
 */
class Bus extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'bus';
    }
}
