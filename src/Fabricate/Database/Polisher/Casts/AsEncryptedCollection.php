<?php

namespace Fabricate\Database\Polisher\Casts;

use Fabricate\Contracts\Database\Polisher\Castable;
use Fabricate\Contracts\Database\Polisher\CastsAttributes;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\Core\MagicAliases\Crypt;
use Fabricate\NutsAndBolts\Str;
use InvalidArgumentException;

class AsEncryptedCollection implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Fabricate\Contracts\Database\Polisher\CastsAttributes<\Fabricate\NutsAndBolts\Collection<array-key, mixed>, iterable>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            public function __construct(protected array $arguments)
            {
                $this->arguments = array_pad(array_values($this->arguments), 2, '');
            }

            public function get($model, $key, $value, $attributes)
            {
                $collectionClass = empty($this->arguments[0]) ? Collection::class : $this->arguments[0];

                if (! is_a($collectionClass, Collection::class, true)) {
                    throw new InvalidArgumentException('The provided class must extend ['.Collection::class.'].');
                }

                if (! isset($attributes[$key])) {
                    return null;
                }

                $instance = new $collectionClass(Json::decode(Crypt::decryptString($attributes[$key])));

                if (! isset($this->arguments[1]) || ! $this->arguments[1]) {
                    return $instance;
                }

                if (is_string($this->arguments[1])) {
                    $this->arguments[1] = Str::parseCallback($this->arguments[1]);
                }

                return is_callable($this->arguments[1])
                    ? $instance->map($this->arguments[1])
                    : $instance->mapInto($this->arguments[1][0]);
            }

            public function set($model, $key, $value, $attributes)
            {
                if (! is_null($value)) {
                    return [$key => Crypt::encryptString(Json::encode($value))];
                }

                return null;
            }
        };
    }

    /**
     * Specify the type of object each item in the collection should be mapped to.
     *
     * @param  array{class-string, string}|class-string  $map
     * @return string
     */
    public static function of($map)
    {
        return static::using('', $map);
    }

    /**
     * Specify the collection for the cast.
     *
     * @param  class-string  $class
     * @param  array{class-string, string}|class-string|null  $map
     * @return string
     */
    public static function using($class, $map = null)
    {
        if (is_array($map) && is_callable($map)) {
            $map = $map[0].'@'.$map[1];
        }

        return static::class.':'.implode(',', [$class, $map]);
    }
}
