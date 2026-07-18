<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

use Fabricate\Contracts\Bus\Dispatcher as BusDispatcherContract;

/**
 * @method static mixed dispatch(mixed $command)
 * @method static mixed dispatchSync(mixed $command, mixed $handler = null)
 * @method static mixed dispatchNow(mixed $command, mixed $handler = null)
 * @method static void bulk(iterable $jobs)
 * @method static bool hasCommandHandler(mixed $command)
 * @method static mixed getCommandHandler(mixed $command)
 * @method static mixed dispatchToQueue(mixed $command)
 * @method static void dispatchAfterResponse(mixed $command, mixed $handler = null)
 * @method static \Fabricate\Bus\Dispatcher pipeThrough(array $pipes)
 * @method static \Fabricate\Bus\Dispatcher map(array $map)
 * @method static string|null resolveConnectionFromQueueRoute(object $queueable)
 * @method static string|null resolveQueueFromQueueRoute(object $queueable)
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
        return BusDispatcherContract::class;
    }
}
