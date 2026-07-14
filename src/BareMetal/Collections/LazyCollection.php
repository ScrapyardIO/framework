<?php

namespace ScrapyardIO\NutsAndBolts;

use ArrayIterator;
use Closure;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use ScrapyardIO\NutsAndBolts\Arrayable;
use ScrapyardIO\NutsAndBolts\Concerns\EnumeratesValues;
use ScrapyardIO\NutsAndBolts\Concerns\Macroable;
use InvalidArgumentException;
use IteratorAggregate;
use ScrpayardIO\NutsAndBolts\Collection;
use stdClass;
use Traversable;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @implements \ScrapyardIO\NutsAndBolts\Enumerable<TKey, TValue>
 */
class LazyCollection implements CanBeEscapedWhenCastToString, Enumerable
{
    /**
     * @use \ScrapyardIO\NutsAndBolts\Concerns\EnumeratesValues<TKey, TValue>
     */
    use EnumeratesValues, Macroable;

    /**
     * The source from which to generate items.
     */
    public $source;

    /**
     * Create a new lazy collection instance.
     */
    public function __construct(Arrayable|iterable|Closure|self|null $source = null)
    {
        if ($source instanceof Closure || $source instanceof self) {
            $this->source = $source;
        } elseif (is_null($source)) {
            $this->source = static::empty();
        } elseif ($source instanceof Generator) {
            throw new InvalidArgumentException(
                'Generators should not be passed directly to LazyCollection. Instead, pass a generator function.'
            );
        } else {
            $this->source = $this->getArrayableItems($source);
        }
    }

    /**
     * Create a new instance of the collection.
     */
    protected function newInstance(Arrayable|iterable|Closure|self|null $items = []): static
    {
        return new static($items);
    }

    /**
     * Create a new collection instance if the value isn't one already.
     */
    public static function make($items = [], ...$args): static
    {
        return new static($items, ...$args);
    }

    /**
     * Create a collection with the given range.
     */
    public static function range($from, $to, $step = 1, ...$args): static
    {
        if ($step == 0) {
            throw new InvalidArgumentException('Step value cannot be zero.');
        }

        return new static(function () use ($from, $to, $step) {
            if ($from <= $to) {
                for (; $from <= $to; $from += abs($step)) {
                    yield $from;
                }
            } else {
                for (; $from >= $to; $from -= abs($step)) {
                    yield $from;
                }
            }
        });
    }

    /**
     * Get all items in the enumerable.
     */
    public function all(): array
    {
        if (is_array($this->source)) {
            return $this->source;
        }

        return iterator_to_array($this->getIterator());
    }

    /**
     * Eager load all items into a new lazy collection backed by an array.
     */
    public function eager(): static
    {
        return new static($this->all());
    }

    /**
     * Cache values as they're enumerated.
     */
    public function remember(): static
    {
        $iterator = $this->getIterator();

        $iteratorIndex = 0;

        $cache = [];

        return new static(function () use ($iterator, &$iteratorIndex, &$cache) {
            for ($index = 0; true; $index++) {
                if (array_key_exists($index, $cache)) {
                    yield $cache[$index][0] => $cache[$index][1];

                    continue;
                }

                if ($iteratorIndex < $index) {
                    $iterator->next();

                    $iteratorIndex++;
                }

                if (! $iterator->valid()) {
                    break;
                }

                $cache[$index] = [$iterator->key(), $iterator->current()];

                yield $cache[$index][0] => $cache[$index][1];
            }
        });
    }

    /**
     * Get the median of a given key.
     */
    public function median(string|array|null $key = null): float|int|null
    {
        return $this->collect()->median($key);
    }

    /**
     * Get the mode of a given key.
     */
    public function mode(string|array|null $key = null): ?array
    {
        return $this->collect()->mode($key);
    }

    /**
     * Collapse the collection of items into a single array.
     */
    public function collapse(): static
    {
        return new static(function () {
            foreach ($this as $values) {
                if (is_array($values) || $values instanceof Enumerable) {
                    foreach ($values as $value) {
                        yield $value;
                    }
                }
            }
        });
    }

    /**
     * Collapse the collection of items into a single array while preserving its keys.
     */
    public function collapseWithKeys(): static
    {
        return new static(function () {
            foreach ($this as $values) {
                if (is_array($values) || $values instanceof Enumerable) {
                    foreach ($values as $key => $value) {
                        yield $key => $value;
                    }
                }
            }
        });
    }

    /**
     * Determine if an item exists in the enumerable.
     */
    public function contains($key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1 && $this->useAsCallable($key)) {
            $placeholder = new stdClass;

            return $this->first($key, $placeholder) !== $placeholder;
        }

        if (func_num_args() === 1) {
            $needle = $key;

            foreach ($this as $value) {
                if ($value == $needle) {
                    return true;
                }
            }

            return false;
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Determine if an item exists, using strict comparison.
     */
    public function containsStrict($key, mixed $value = null): bool
    {
        if (func_num_args() === 2) {
            return $this->contains(fn ($item) => data_get($item, $key) === $value);
        }

        if ($this->useAsCallable($key)) {
            return ! is_null($this->first($key));
        }

        foreach ($this as $item) {
            if ($item === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if an item is not contained in the enumerable.
     */
    public function doesntContain(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        return ! $this->contains(...func_get_args());
    }

    /**
     * Determine if an item is not contained in the enumerable, using strict comparison.
     */
    public function doesntContainStrict(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        return ! $this->containsStrict(...func_get_args());
    }

    /**
     * Cross join with the given lists, returning all possible permutations.
     */
    #[\Override]
    public function crossJoin(...$arrays): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Count the enumerable items by a given key.
     */
    #[\Override]
    public function countBy($countBy = null): static
    {
        $countBy = is_null($countBy)
            ? $this->identity()
            : $this->valueRetriever($countBy);

        return new static(function () use ($countBy) {
            $counts = [];

            foreach ($this as $key => $value) {
                $group = enum_value($countBy($value, $key));

                if (empty($counts[$group])) {
                    $counts[$group] = 0;
                }

                $counts[$group]++;
            }

            yield from $counts;
        });
    }

    /**
     * Get the items in the collection that are not present in the given items.
     */
    #[\Override]
    public function diff($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Get the items in the collection that are not present in the given items, using the callback.
     */
    #[\Override]
    public function diffUsing($items, callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items.
     */
    #[\Override]
    public function diffAssoc($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items, using the callback.
     */
    #[\Override]
    public function diffAssocUsing($items, callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     */
    #[\Override]
    public function diffKeys($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Get the items in the collection whose keys are not present in the given items, using the callback.
     */
    #[\Override]
    public function diffKeysUsing($items, callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Retrieve duplicate items from the collection.
     */
    #[\Override]
    public function duplicates($callback = null, $strict = false): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Retrieve duplicate items from the collection using strict comparison.
     */
    #[\Override]
    public function duplicatesStrict($callback = null): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Get all items except for those with the specified keys.
     */
    #[\Override]
    public function except($keys): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Run a filter over each of the items.
     */
    public function filter(?callable $callback = null): static
    {
        if (is_null($callback)) {
            $callback = fn ($value) => (bool) $value;
        }

        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                if ($callback($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * Get the first item from the enumerable passing the given truth test.
     */
    public function first(?callable $callback = null, $default = null)
    {
        $iterator = $this->getIterator();

        if (is_null($callback)) {
            if (! $iterator->valid()) {
                return value($default);
            }

            return $iterator->current();
        }

        foreach ($iterator as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * Get a flattened list of the items in the collection.
     */
    public function flatten($depth = INF): static
    {
        $instance = new static(function () use ($depth) {
            foreach ($this as $item) {
                if (! is_array($item) && ! $item instanceof Enumerable) {
                    yield $item;
                } elseif ($depth === 1) {
                    yield from $item;
                } else {
                    yield from (new static($item))->flatten($depth - 1);
                }
            }
        });

        return $instance->values();
    }

    /**
     * Flip the items in the collection.
     */
    public function flip(): static
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $value => $key;
            }
        });
    }

    /**
     * Get an item by key.
     */
    public function get($key, $default = null)
    {
        if (is_null($key)) {
            return null;
        }

        foreach ($this as $outerKey => $outerValue) {
            if ($outerKey == $key) {
                return $outerValue;
            }
        }

        return value($default);
    }

    /**
     * Group an associative array by a field or using a callback.
     */
    #[\Override]
    public function groupBy($groupBy, $preserveKeys = false): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Key an associative array by a field or using a callback.
     */
    #[\Override]
    public function keyBy($keyBy): static
    {
        return new static(function () use ($keyBy) {
            $keyBy = $this->valueRetriever($keyBy);

            foreach ($this as $key => $item) {
                $resolvedKey = $keyBy($item, $key);

                if ($resolvedKey instanceof \UnitEnum) {
                    $resolvedKey = enum_value($resolvedKey);
                }

                if (is_object($resolvedKey)) {
                    $resolvedKey = (string) $resolvedKey;
                }

                yield $resolvedKey => $item;
            }
        });
    }

    /**
     * Determine if an item exists in the collection by key.
     */
    public function has($key): bool
    {
        $keys = array_flip(is_array($key) ? $key : func_get_args());

        foreach ($this as $key => $value) {
            unset($keys[$key]);

            if (empty($keys)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if any of the keys exist in the collection.
     */
    public function hasAny($key): bool
    {
        $keys = array_flip(is_array($key) ? $key : func_get_args());

        foreach ($this as $key => $value) {
            if (array_key_exists($key, $keys)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Concatenate values of a given key as a string.
     */
    public function implode($value, $glue = null): string
    {
        return $this->collect()->implode(...func_get_args());
    }

    /**
     * Intersect the collection with the given items.
     */
    #[\Override]
    public function intersect($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Intersect the collection with the given items, using the callback.
     */
    #[\Override]
    public function intersectUsing($items, callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Intersect the collection with the given items with additional index check.
     */
    #[\Override]
    public function intersectAssoc($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Intersect the collection with the given items with additional index check, using the callback.
     */
    #[\Override]
    public function intersectAssocUsing($items, callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Intersect the collection with the given items by key.
     */
    #[\Override]
    public function intersectByKeys($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Determine if the items are empty or not.
     */
    public function isEmpty(): bool
    {
        return ! $this->getIterator()->valid();
    }

    /**
     * Determine if the collection contains a single item.
     *
     * @deprecated 12.49.0 Use the `hasSole()` method instead.
     */
    public function containsOneItem(?callable $callback = null): bool
    {
        return $this->hasSole($callback);
    }

    /**
     * Determine if the collection contains multiple items.
     *
     * @deprecated 12.50.0 Use the `hasMany()` method instead.
     */
    public function containsManyItems(): bool
    {
        return $this->hasMany();
    }

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
     */
    public function join($glue, $finalGlue = ''): string
    {
        return $this->collect()->join(...func_get_args());
    }

    /**
     * Get the keys of the collection items.
     */
    public function keys(): static
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $key;
            }
        });
    }

    /**
     * Get the last item from the collection.
     */
    public function last(?callable $callback = null, $default = null)
    {
        $needle = $placeholder = new stdClass;

        foreach ($this as $key => $value) {
            if (is_null($callback) || $callback($value, $key)) {
                $needle = $value;
            }
        }

        return $needle === $placeholder ? value($default) : $needle;
    }

    /**
     * Get the values of a given key.
     */
    public function pluck($value, $key = null): static
    {
        return new static(function () use ($value, $key) {
            [$value, $key] = $this->explodePluckParameters($value, $key);

            foreach ($this as $item) {
                $itemValue = $value instanceof Closure
                    ? $value($item)
                    : data_get($item, $value);

                if (is_null($key)) {
                    yield $itemValue;
                } else {
                    $itemKey = $key instanceof Closure
                        ? $key($item)
                        : data_get($item, $key);

                    if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                        $itemKey = (string) $itemKey;
                    }

                    yield $itemKey => $itemValue;
                }
            }
        });
    }

    /**
     * Run a map over each of the items.
     */
    public function map(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    /**
     * Run a dictionary map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     */
    #[\Override]
    public function mapToDictionary(callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     */
    public function mapWithKeys(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield from $callback($value, $key);
            }
        });
    }

    /**
     * Merge the collection with the given items.
     */
    #[\Override]
    public function merge($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Recursively merge the collection with the given items.
     */
    #[\Override]
    public function mergeRecursive($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Multiply the items in the collection by the multiplier.
     */
    public function multiply(int $multiplier): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Create a collection by using this collection for keys and another for its values.
     */
    public function combine($values): static
    {
        return new static(function () use ($values) {
            $values = $this->makeIterator($values);

            $errorMessage = 'Both parameters should have an equal number of elements';

            foreach ($this as $key) {
                if (! $values->valid()) {
                    trigger_error($errorMessage, E_USER_WARNING);

                    break;
                }

                yield $key => $values->current();

                $values->next();
            }

            if ($values->valid()) {
                trigger_error($errorMessage, E_USER_WARNING);
            }
        });
    }

    /**
     * Union the collection with the given items.
     */
    #[\Override]
    public function union($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Create a new collection consisting of every n-th element.
     */
    public function nth($step, $offset = 0): static
    {
        if ($step < 1) {
            throw new InvalidArgumentException('Step value must be at least 1.');
        }

        return new static(function () use ($step, $offset) {
            $position = 0;

            foreach ($this->slice($offset) as $item) {
                if ($position % $step === 0) {
                    yield $item;
                }

                $position++;
            }
        });
    }

    /**
     * Get the items with the specified keys.
     */
    public function only($keys): static
    {
        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        } elseif (! is_null($keys)) {
            $keys = is_array($keys) ? $keys : func_get_args();
        }

        return new static(function () use ($keys) {
            if (is_null($keys)) {
                yield from $this;
            } else {
                $keys = array_flip($keys);

                foreach ($this as $key => $value) {
                    if (array_key_exists($key, $keys)) {
                        yield $key => $value;

                        unset($keys[$key]);

                        if (empty($keys)) {
                            break;
                        }
                    }
                }
            }
        });
    }

    /**
     * Select specific values from the items within the collection.
     */
    public function select($keys): static
    {
        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        } elseif (! is_null($keys)) {
            $keys = is_array($keys) ? $keys : func_get_args();
        }

        return new static(function () use ($keys) {
            if (is_null($keys)) {
                yield from $this;
            } else {
                foreach ($this as $item) {
                    $result = [];

                    foreach ($keys as $key) {
                        if (Arr::accessible($item) && Arr::exists($item, $key)) {
                            $result[$key] = $item[$key];
                        } elseif (is_object($item) && isset($item->{$key})) {
                            $result[$key] = $item->{$key};
                        }
                    }

                    yield $result;
                }
            }
        });
    }

    /**
     * Push all of the given items onto the collection.
     */
    public function concat($source): static
    {
        return (new static(function () use ($source) {
            yield from $this;
            yield from $source;
        }))->values();
    }

    /**
     * Get one or a specified number of items randomly from the collection.
     */
    public function random($number = null, $preserveKeys = false)
    {
        $result = $this->collect()->random(...func_get_args());

        return is_null($number) ? $result : new static($result);
    }

    /**
     * Replace the collection items with the given items.
     */
    public function replace($items): static
    {
        return new static(function () use ($items) {
            $items = $this->getArrayableItems($items);

            foreach ($this as $key => $value) {
                if (array_key_exists($key, $items)) {
                    yield $key => $items[$key];

                    unset($items[$key]);
                } else {
                    yield $key => $value;
                }
            }

            foreach ($items as $key => $value) {
                yield $key => $value;
            }
        });
    }

    /**
     * Recursively replace the collection items with the given items.
     */
    #[\Override]
    public function replaceRecursive($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Reverse items order.
     */
    #[\Override]
    public function reverse(): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     */
    public function search($value, $strict = false)
    {
        $predicate = $this->useAsCallable($value)
            ? $value
            : function ($item) use ($value, $strict) {
                return $strict ? $item === $value : $item == $value;
            };

        foreach ($this as $key => $item) {
            if ($predicate($item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Get the item before the given item.
     */
    public function before($value, $strict = false)
    {
        $previous = null;

        $predicate = $this->useAsCallable($value)
            ? $value
            : function ($item) use ($value, $strict) {
                return $strict ? $item === $value : $item == $value;
            };

        foreach ($this as $key => $item) {
            if ($predicate($item, $key)) {
                return $previous;
            }

            $previous = $item;
        }

        return null;
    }

    /**
     * Get the item after the given item.
     */
    public function after($value, $strict = false)
    {
        $found = false;

        $predicate = $this->useAsCallable($value)
            ? $value
            : function ($item) use ($value, $strict) {
                return $strict ? $item === $value : $item == $value;
            };

        foreach ($this as $key => $item) {
            if ($found) {
                return $item;
            }

            if ($predicate($item, $key)) {
                $found = true;
            }
        }

        return null;
    }

    /**
     * Shuffle the items in the collection.
     */
    #[\Override]
    public function shuffle(): static
    {
        return $this->passthru(__FUNCTION__, []);
    }

    /**
     * Create chunks representing a "sliding window" view of the items in the collection.
     */
    public function sliding($size = 2, $step = 1): static
    {
        if ($size < 1) {
            throw new InvalidArgumentException('Size value must be at least 1.');
        } elseif ($step < 1) {
            throw new InvalidArgumentException('Step value must be at least 1.');
        }

        return new static(function () use ($size, $step) {
            $iterator = $this->getIterator();

            $chunk = [];

            while ($iterator->valid()) {
                $chunk[$iterator->key()] = $iterator->current();

                if (count($chunk) == $size) {
                    yield (new static($chunk))->tap(function () use (&$chunk, $step) {
                        $chunk = array_slice($chunk, $step, null, true);
                    });

                    // If the $step between chunks is bigger than each chunk's $size
                    // we will skip the extra items (which should never be in any
                    // chunk) before we continue to the next chunk in the loop.
                    if ($step > $size) {
                        $skip = $step - $size;

                        for ($i = 0; $i < $skip && $iterator->valid(); $i++) {
                            $iterator->next();
                        }
                    }
                }

                $iterator->next();
            }
        });
    }

    /**
     * Skip the first {$count} items.
     */
    public function skip($count): static
    {
        return new static(function () use ($count) {
            $iterator = $this->getIterator();

            while ($iterator->valid() && $count--) {
                $iterator->next();
            }

            while ($iterator->valid()) {
                yield $iterator->key() => $iterator->current();

                $iterator->next();
            }
        });
    }

    /**
     * Skip items in the collection until the given condition is met.
     */
    public function skipUntil($value): static
    {
        $callback = $this->useAsCallable($value) ? $value : $this->equality($value);

        return $this->skipWhile($this->negate($callback));
    }

    /**
     * Skip items in the collection while the given condition is met.
     */
    public function skipWhile($value): static
    {
        $callback = $this->useAsCallable($value) ? $value : $this->equality($value);

        return new static(function () use ($callback) {
            $iterator = $this->getIterator();

            while ($iterator->valid() && $callback($iterator->current(), $iterator->key())) {
                $iterator->next();
            }

            while ($iterator->valid()) {
                yield $iterator->key() => $iterator->current();

                $iterator->next();
            }
        });
    }

    /**
     * Slice the underlying collection array.
     */
    #[\Override]
    public function slice($offset, $length = null): static
    {
        if ($offset < 0 || $length < 0) {
            return $this->passthru(__FUNCTION__, func_get_args());
        }

        $instance = $this->skip($offset);

        return is_null($length) ? $instance : $instance->take($length);
    }

    /**
     * Split a collection into a certain number of groups.
     */
    #[\Override]
    public function split($numberOfGroups): static
    {
        if ($numberOfGroups < 1) {
            throw new InvalidArgumentException('Number of groups must be at least 1.');
        }

        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Get the first item in the collection, but only if exactly one item exists. Otherwise, throw an exception.
     */
    public function sole($key = null, $operator = null, $value = null)
    {
        $filter = func_num_args() > 1
            ? $this->operatorForWhere(...func_get_args())
            : $key;

        return $this
            ->unless($filter == null)
            ->filter($filter)
            ->take(2)
            ->collect()
            ->sole();
    }

    /**
     * Determine if the collection contains a single item or a single item matching the given criteria.
     */
    public function hasSole($key = null, $operator = null, $value = null): bool
    {
        $filter = func_num_args() > 1
            ? $this->operatorForWhere(...func_get_args())
            : $key;

        return $this
            ->unless($filter == null)
            ->filter($filter)
            ->take(2)
            ->count() === 1;
    }

    /**
     * Get the first item in the collection but throw an exception if no matching items exist.
     */
    public function firstOrFail($key = null, $operator = null, $value = null)
    {
        $filter = func_num_args() > 1
            ? $this->operatorForWhere(...func_get_args())
            : $key;

        return $this
            ->unless($filter == null)
            ->filter($filter)
            ->take(1)
            ->collect()
            ->firstOrFail();
    }

    /**
     * Chunk the collection into chunks of the given size.
     */
    public function chunk($size, $preserveKeys = true): static
    {
        if ($size <= 0) {
            return static::empty();
        }

        $add = match ($preserveKeys) {
            true => fn (array &$chunk, Traversable $iterator) => $chunk[$iterator->key()] = $iterator->current(),
            false => fn (array &$chunk, Traversable $iterator) => $chunk[] = $iterator->current(),
        };

        return new static(function () use ($size, $add) {
            $iterator = $this->getIterator();

            while ($iterator->valid()) {
                $chunk = [];

                while (true) {
                    $add($chunk, $iterator);

                    if (count($chunk) < $size) {
                        $iterator->next();

                        if (! $iterator->valid()) {
                            break;
                        }
                    } else {
                        break;
                    }
                }

                yield new static($chunk);

                $iterator->next();
            }
        });
    }

    /**
     * Split a collection into a certain number of groups, and fill the first groups completely.
     */
    public function splitIn($numberOfGroups): static
    {
        if ($numberOfGroups < 1) {
            throw new InvalidArgumentException('Number of groups must be at least 1.');
        }

        return $this->chunk((int) ceil($this->count() / $numberOfGroups));
    }

    /**
     * Chunk the collection into chunks with a callback.
     */
    public function chunkWhile(callable $callback): static
    {
        return new static(function () use ($callback) {
            $iterator = $this->getIterator();

            $chunk = new Collection;

            if ($iterator->valid()) {
                $chunk[$iterator->key()] = $iterator->current();

                $iterator->next();
            }

            while ($iterator->valid()) {
                if (! $callback($iterator->current(), $iterator->key(), $chunk)) {
                    yield new static($chunk);

                    $chunk = new Collection;
                }

                $chunk[$iterator->key()] = $iterator->current();

                $iterator->next();
            }

            if ($chunk->isNotEmpty()) {
                yield new static($chunk);
            }
        });
    }

    /**
     * Sort through each item with a callback.
     */
    #[\Override]
    public function sort($callback = null): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Sort items in descending order.
     */
    #[\Override]
    public function sortDesc($options = SORT_REGULAR): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Sort the collection using the given callback.
     */
    #[\Override]
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Sort the collection in descending order using the given callback.
     */
    #[\Override]
    public function sortByDesc($callback, $options = SORT_REGULAR): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Sort the collection keys.
     */
    #[\Override]
    public function sortKeys($options = SORT_REGULAR, $descending = false): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Sort the collection keys in descending order.
     */
    #[\Override]
    public function sortKeysDesc($options = SORT_REGULAR): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Sort the collection keys using a callback.
     */
    #[\Override]
    public function sortKeysUsing(callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Take the first or last {$limit} items.
     */
    public function take($limit): static
    {
        if ($limit < 0) {
            return new static(function () use ($limit) {
                $limit = abs($limit);
                $ringBuffer = [];
                $position = 0;

                foreach ($this as $key => $value) {
                    $ringBuffer[$position] = [$key, $value];
                    $position = ($position + 1) % $limit;
                }

                for ($i = 0, $end = min($limit, count($ringBuffer)); $i < $end; $i++) {
                    $pointer = ($position + $i) % $limit;
                    yield $ringBuffer[$pointer][0] => $ringBuffer[$pointer][1];
                }
            });
        }

        return new static(function () use ($limit) {
            $iterator = $this->getIterator();

            while ($limit--) {
                if (! $iterator->valid()) {
                    break;
                }

                yield $iterator->key() => $iterator->current();

                if ($limit) {
                    $iterator->next();
                }
            }
        });
    }

    /**
     * Take items in the collection until the given condition is met.
     */
    public function takeUntil($value): static
    {
        $callback = $this->useAsCallable($value) ? $value : $this->equality($value);

        return new static(function () use ($callback) {
            foreach ($this as $key => $item) {
                if ($callback($item, $key)) {
                    break;
                }

                yield $key => $item;
            }
        });
    }

    /**
     * Take items in the collection until a given point in time, with an optional callback on timeout.
     */
    public function takeUntilTimeout(DateTimeInterface $timeout, ?callable $callback = null): static
    {
        $timeout = $timeout->getTimestamp();

        return new static(function () use ($timeout, $callback) {
            if ($this->now() >= $timeout) {
                if ($callback) {
                    $callback(null, null);
                }

                return;
            }

            foreach ($this as $key => $value) {
                yield $key => $value;

                if ($this->now() >= $timeout) {
                    if ($callback) {
                        $callback($value, $key);
                    }

                    break;
                }
            }
        });
    }

    /**
     * Take items in the collection while the given condition is met.
     */
    public function takeWhile($value): static
    {
        $callback = $this->useAsCallable($value) ? $value : $this->equality($value);

        return $this->takeUntil(fn ($item, $key) => ! $callback($item, $key));
    }

    /**
     * Pass each item in the collection to the given callback, lazily.
     */
    public function tapEach(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                $callback($value, $key);

                yield $key => $value;
            }
        });
    }

    /**
     * Throttle the values, releasing them at most once per the given seconds.
     */
    public function throttle(float $seconds): static
    {
        return new static(function () use ($seconds) {
            $microseconds = $seconds * 1_000_000;

            foreach ($this as $key => $value) {
                $fetchedAt = $this->preciseNow();

                yield $key => $value;

                $sleep = $microseconds - ($this->preciseNow() - $fetchedAt);

                $this->usleep((int) $sleep);
            }
        });
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     */
    public function dot($depth = INF): static
    {
        return $this->passthru(__FUNCTION__, [$depth]);
    }

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     */
    #[\Override]
    public function undot(): static
    {
        return $this->passthru(__FUNCTION__, []);
    }

    /**
     * Return only unique items from the collection array.
     */
    public function unique(callable|string|null $key = null, bool $strict = false): static
    {
        $callback = $this->valueRetriever($key);

        return new static(function () use ($callback, $strict) {
            $exists = [];

            foreach ($this as $key => $item) {
                if (! in_array($id = $callback($item, $key), $exists, $strict)) {
                    yield $key => $item;

                    $exists[] = $id;
                }
            }
        });
    }

    /**
     * Reset the keys on the underlying array.
     */
    public function values(): static
    {
        return new static(function () {
            foreach ($this as $item) {
                yield $item;
            }
        });
    }

    /**
     * Run the given callback every time the interval has passed.
     */
    public function withHeartbeat(DateInterval|int $interval, callable $callback): static
    {
        $seconds = is_int($interval) ? $interval : $this->intervalSeconds($interval);

        return new static(function () use ($seconds, $callback) {
            $start = $this->now();

            foreach ($this as $key => $value) {
                $now = $this->now();

                if (($now - $start) >= $seconds) {
                    $callback();

                    $start = $now;
                }

                yield $key => $value;
            }
        });
    }

    /**
     * Get the total seconds from the given interval.
     */
    protected function intervalSeconds(DateInterval $interval): int
    {
        $start = new DateTimeImmutable();

        return $start->add($interval)->getTimestamp() - $start->getTimestamp();
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * e.g. new LazyCollection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     */
    public function zip($items): static
    {
        $iterables = func_get_args();

        return new static(function () use ($iterables) {
            $iterators = (new Collection($iterables))
                ->map(fn ($iterable) => $this->makeIterator($iterable))
                ->prepend($this->getIterator());

            while ($iterators->contains->valid()) {
                yield new static($iterators->map->current());

                $iterators->each->next();
            }
        });
    }

    /**
     * Pad collection to the specified length with a value.
     */
    #[\Override]
    public function pad($size, $value): static
    {
        if ($size < 0) {
            return $this->passthru(__FUNCTION__, func_get_args());
        }

        return new static(function () use ($size, $value) {
            $yielded = 0;

            foreach ($this as $index => $item) {
                yield $index => $item;

                $yielded++;
            }

            while ($yielded++ < $size) {
                yield $value;
            }
        });
    }

    /**
     * Get the values iterator.
     */
    public function getIterator(): Traversable
    {
        return $this->makeIterator($this->source);
    }

    /**
     * Count the number of items in the collection.
     */
    public function count(): int
    {
        if (is_array($this->source)) {
            return count($this->source);
        }

        return iterator_count($this->getIterator());
    }

    /**
     * Make an iterator from the given source.
     */
    protected function makeIterator($source): Traversable
    {
        if ($source instanceof IteratorAggregate) {
            return $source->getIterator();
        }

        if (is_array($source)) {
            return new ArrayIterator($source);
        }

        if (is_callable($source)) {
            $maybeTraversable = $source();

            return $maybeTraversable instanceof Traversable
                ? $maybeTraversable
                : new ArrayIterator(Arr::wrap($maybeTraversable));
        }

        return new ArrayIterator((array) $source);
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     */
    protected function explodePluckParameters($value, $key): array
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) || $key instanceof Closure ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Pass this lazy collection through a method on the collection class.
     */
    protected function passthru(string $method, array $params): static
    {
        return new static(function () use ($method, $params) {
            yield from $this->collect()->$method(...$params);
        });
    }

    /**
     * Get the current time.
     */
    protected function now(): int
    {
        return class_exists(Carbon::class)
            ? Carbon::now()->getTimestamp()
            : time();
    }

    /**
     * Get the precise current time.
     */
    protected function preciseNow(): float
    {
        return class_exists(Carbon::class)
            ? Carbon::now()->getPreciseTimestamp()
            : microtime(true) * 1_000_000;
    }

    /**
     * Sleep for the given amount of microseconds.
     */
    protected function usleep(int $microseconds): void
    {
        if ($microseconds <= 0) {
            return;
        }

        usleep($microseconds);
    }
}
