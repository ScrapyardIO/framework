<?php

namespace GeneralPurposeIO\Core\MagicAliases;

use Closure;
use GeneralPurposeIO\Contracts\Core\ByteSource;
use GeneralPurposeIO\Contracts\Core\EdgeSource;
use GeneralPurposeIO\Contracts\Core\GPIOResourceDriver;
use GeneralPurposeIO\Contracts\Core\Recurrence;
use Voyager\IOPools\Presumption;
use Voyager\MagicAliases\MagicAlias;

/**
 * The gpio dock resource. Bound as `gpio` only when the resource was
 * registered on the dock (gpio.io_pools.enabled and io-pool present).
 *
 * @method static GPIOResourceDriver watch(EdgeSource $source, bool $rising = true, bool $falling = false)
 * @method static GPIOResourceDriver unwatch(EdgeSource $source)
 * @method static GPIOResourceDriver receive(ByteSource $source, int $max_bytes = 4096)
 * @method static GPIOResourceDriver stopReceiving(ByteSource $source)
 * @method static Presumption defer(string $name, Closure $work, ?Closure $envelope = null)
 * @method static ?Presumption inFlight(string $name)
 * @method static Recurrence every(string $name, Closure $work, int $ticks = 1)
 * @method static ?Recurrence recurring(string $name)
 * @method static Presumption stream(string $name, Closure $write, string $bytes, int $chunk)
 * @method static ?Presumption streaming(string $name)
 */
class GPIO extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'gpio';
    }
}
