<?php

namespace Fabricate\Config;

use Closure;
use ArrayAccess;
use InvalidArgumentException;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Concerns\Macroable;
use Fabricate\Contracts\Config\Repository as ConfigContract;

class Repository implements ArrayAccess, ConfigContract
{
    use Macroable;

    /**
     * All of the configuration items.
     *
     * @var array<string,mixed>
     */
    protected array $items = [];

    /**
     * Create a new configuration repository.
     *
     * @param  array  $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param array|string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(array|string $key, mixed $default = null): mixed
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get($this->items, $key, $default);
    }

    /**
     * Get many configuration values.
     *
     * @param array<string|int,mixed> $keys
     * @return array<string,mixed>
     */
    public function getMany(array $keys): array
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $config[$key] = Arr::get($this->items, $key, $default);
        }

        return $config;
    }

    /**
     * Get the specified string configuration value.
     *
     * @param  string  $key
     * @param string|(\Closure():(string|null))|null $default
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function string(string $key, string|Closure|null $default = null): string
    {
        $value = $this->get($key, $default);

        if (! is_string($value)) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a string, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified integer configuration value.
     *
     * @param  string  $key
     * @param int|(\Closure():(int|null))|null $default
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    public function integer(string $key, int|Closure|null $default = null): int
    {
        $value = $this->get($key, $default);

        if (! is_int($value)) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an integer, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified float configuration value.
     *
     * @param  string  $key
     * @param float|(\Closure():(float|null))|null $default
     * @return float
     *
     * @throws \InvalidArgumentException
     */
    public function float(string $key, float|Closure|null $default = null): float
    {
        $value = $this->get($key, $default);

        if (! is_float($value)) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a float, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified boolean configuration value.
     *
     * @param  string  $key
     * @param bool|(\Closure():(bool|null))|null $default
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function boolean(string $key, bool|Closure|null $default = null): bool
    {
        $value = $this->get($key, $default);

        if (! is_bool($value)) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a boolean, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified array configuration value.
     *
     * @param  string  $key
     * @param (\Closure():(array<array-key, mixed>|null))|array<array-key, mixed>|null $default
     * @return array<array-key, mixed>
     *
     * @throws \InvalidArgumentException
     */
    public function array(string $key, array|Closure|null $default = null): array
    {
        $value = $this->get($key, $default);

        if (! is_array($value)) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an array, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified array configuration value as a collection.
     *
     * @param  string  $key
     * @param (\Closure():(array<array-key, mixed>|null))|array<array-key, mixed>|null $default
     * @return Collection<array-key, mixed>
     */
    public function collection(string $key, array|Closure|null $default = null): Collection
    {
        return new Collection($this->array($key, $default));
    }

    /**
     * Set a given configuration value.
     *
     * @param array|string $key
     * @param mixed|null $value
     * @return void
     */
    public function set(array|string $key, mixed $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param string $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend(string $key, mixed $value): void
    {
        $array = $this->get($key, []);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param string $key
     * @param  mixed  $value
     * @return void
     */
    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Get a configuration option.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set a configuration option.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->set($offset, null);
    }
}
