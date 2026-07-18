<?php

namespace Fabricate\NutsAndBolts;

use Fabricate\NutsAndBolts\Contracts\Enumerable;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @mixin Enumerable<TKey, TValue>
 * @mixin TValue
 */
class HigherOrderCollectionProxy
{
    /**
     * The collection being operated on.
     *
     * @var Enumerable<TKey, TValue>
     */
    protected Enumerable $collection;

    /**
     * The method being proxied.
     *
     * @var string
     */
    protected string $method;

    /**
     * Create a new proxy instance.
     *
     * @param  Enumerable<TKey, TValue>  $collection
     * @param string $method
     */
    public function __construct(Enumerable $collection, string $method)
    {
        $this->method = $method;
        $this->collection = $collection;
    }

    /**
     * Proxy accessing an attribute onto the collection items.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    /**
     * Proxy a method call onto the collection items.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
            return is_string($value)
                ? $value::{$method}(...$parameters)
                : $value->{$method}(...$parameters);
        });
    }
}
