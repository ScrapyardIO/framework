<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\Http\Client\Factory;
use Fabricate\MagicAliases\MagicAlias;

/**
 * @method static \Fabricate\Http\Client\PendingRequest accept(string $contentType)
 * @method static \Fabricate\Http\Client\PendingRequest acceptJson()
 * @method static \Fabricate\Http\Client\PendingRequest asForm()
 * @method static \Fabricate\Http\Client\PendingRequest asJson()
 * @method static \Fabricate\Http\Client\Response get(string $url, array|string|null $query = null)
 * @method static \Fabricate\Http\Client\Response post(string $url, array $data = [])
 * @method static \Fabricate\Http\Client\Response put(string $url, array $data = [])
 * @method static \Fabricate\Http\Client\Response patch(string $url, array $data = [])
 * @method static \Fabricate\Http\Client\Response delete(string $url, array $data = [])
 * @method static \Fabricate\Http\Client\Response send(string $method, string $url, array $options = [])
 * @method static \Fabricate\Http\Client\ResponseSequence sequence(array $responses = [])
 * @method static \Fabricate\Http\Client\Factory preventStrayRequests(bool $prevent = true)
 * @method static void assertSent(callable $callback)
 * @method static void assertNotSent(callable $callback)
 * @method static void assertNothingSent()
 *
 * @see \Fabricate\Http\Client\Factory
 */
class Http extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'http';
    }

    /**
     * Replace outbound requests with deterministic fake responses.
     *
     * @param  callable|array|null  $callback
     */
    public static function fake(callable|array|null $callback = null): Factory
    {
        static::getMagicAliasRoot()->fake($callback);

        return static::getMagicAliasRoot();
    }
}
