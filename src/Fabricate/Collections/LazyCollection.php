<?php

namespace Fabricate\NutsAndBolts;

use Closure;
use stdClass;
use Generator;
use Traversable;
use DateInterval;
use ArrayIterator;
use DateTimeInterface;
use IteratorAggregate;
use DateTimeImmutable;
use InvalidArgumentException;
use Fabricate\NutsAndBolts\Concerns\Macroable;
use Fabricate\Contracts\NutsAndBolts\Arrayable;
use Fabricate\NutsAndBolts\Contracts\Enumerable;
use Fabricate\NutsAndBolts\Concerns\EnumeratesValues;
use Fabricate\Contracts\NutsAndBolts\CanBeEscapedWhenCastToString;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @implements Enumerable<TKey, TValue>
 */
class LazyCollection implements CanBeEscapedWhenCastToString, Enumerable
{
    /**
     * @use EnumeratesValues<TKey, TValue>
     */
    use EnumeratesValues, Macroable;

    /** @var (Closure(): Generator<TKey, TValue, mixed, void>)|static|array<TKey, TValue> */
    public Closure|LazyCollection|array $source;

    /**
     * Create a new lazy collection instance.
     *
     * @param (Closure(): Generator<TKey, TValue, mixed, void>)|Arrayable<TKey, TValue>|iterable<TKey, TValue>|self<TKey, TValue>|null $source
     *
     * @throws InvalidArgumentException
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

    protected function newInstance(Arrayable|iterable|Closure|self|null $items = []): static
    {
        return new static($items);
    }

    #[\Override]
    public static function make(Arrayable|iterable|null $items = [], mixed ...$args): static
    {
        return new static($items, ...$args);
    }

    #[\Override]
    public static function range(int|float|string $from, int|float|string $to, int|float $step = 1, mixed ...$args): static
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

    #[\Override]
    public function all(): array
    {
        if (is_array($this->source)) {
            return $this->source;
        }

        return iterator_to_array($this->getIterator());
    }

    public function eager(): static
    {
        return new static($this->all());
    }

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

    #[\Override]
    public function median(string|array|null $key = null): float|int|null
    {
        return $this->collect()->median($key);
    }

    #[\Override]
    public function mode(string|array|null $key = null): ?array
    {
        return $this->collect()->mode($key);
    }

    #[\Override]
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

    #[\Override]
    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1 && $this->useAsCallable($key)) {
            $placeholder = new stdClass;
            return $this->first($key, $placeholder) !== $placeholder;
        }

        if (func_num_args() === 1) {
            $needle = $key;

            foreach ($this as $item) {
                if ($item == $needle) {
                    return true;
                }
            }

            return false;
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    #[\Override]
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

    #[\Override]
    public function doesntContain(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        return ! $this->contains(...func_get_args());
    }

    #[\Override]
    public function crossJoin(...$arrays): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function countBy(callable|string|null $countBy = null): static
    {
        $countBy = is_null($countBy) ? $this->identity() : $this->valueRetriever($countBy);

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

    #[\Override]
    public function diff($items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function diffUsing($items, callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function diffAssoc(Arrayable|iterable $items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function diffAssocUsing(Arrayable|iterable $items, callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function diffKeys(Arrayable|iterable $items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function diffKeysUsing(Arrayable|array $items, callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function duplicates(callable|string|null $callback = null, bool $strict = false): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function duplicatesStrict(callable|string|null $callback = null): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function except(Enumerable|array $keys): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
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

    #[\Override]
    public function first(?callable $callback = null, mixed $default = null): mixed
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

    #[\Override]
    public function flatten(float|int $depth = INF): static
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

    #[\Override]
    public function flip(): static
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $value => $key;
            }
        });
    }

    #[\Override]
    public function get(int|string $key, mixed $default = null): mixed
    {
        foreach ($this as $outerKey => $outerValue) {
            if ($outerKey == $key) {
                return $outerValue;
            }
        }

        return value($default);
    }

    #[\Override]
    public function groupBy(callable|array|string $groupBy, bool $preserveKeys = false): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function keyBy(callable|array|string $keyBy): static
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

    #[\Override]
    public function has(array|int|string $key): bool
    {
        $keys = array_flip(is_array($key) ? $key : func_get_args());

        foreach ($this as $itemKey => $value) {
            unset($keys[$itemKey]);

            if (empty($keys)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function hasAny(mixed $key): bool
    {
        $keys = array_flip(is_array($key) ? $key : func_get_args());

        foreach ($this as $itemKey => $value) {
            if (array_key_exists($itemKey, $keys)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function implode(callable|string $value, ?string $glue = null): string
    {
        return $this->collect()->implode(...func_get_args());
    }

    #[\Override]
    public function intersect(Arrayable|array $items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function intersectUsing(Arrayable|array $items, callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function intersectAssoc(Arrayable|array $items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function intersectAssocUsing(Arrayable|array $items, callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function intersectByKeys(array|Arrayable $items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function isEmpty(): bool
    {
        return ! $this->getIterator()->valid();
    }

    #[\Override]
    public function containsOneItem(?callable $callback = null): bool
    {
        return $this->hasSole($callback);
    }

    #[\Override]
    public function containsManyItems(?callable $callback = null): bool
    {
        return $this->hasMany($callback);
    }

    #[\Override]
    public function join(string $glue, string $finalGlue = ''): string
    {
        return $this->collect()->join(...func_get_args());
    }

    #[\Override]
    public function keys(): static
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $key;
            }
        });
    }

    #[\Override]
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        $needle = $placeholder = new stdClass;

        foreach ($this as $key => $value) {
            if (is_null($callback) || $callback($value, $key)) {
                $needle = $value;
            }
        }

        return $needle === $placeholder ? value($default) : $needle;
    }

    #[\Override]
    public function pluck(array|string $value, ?string $key = null): static
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

    #[\Override]
    public function map(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    #[\Override]
    public function mapToDictionary(callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function mapWithKeys(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield from $callback($value, $key);
            }
        });
    }

    #[\Override]
    public function merge(Arrayable|array $items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function mergeRecursive(Arrayable|array $items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    public function multiply(int $multiplier): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function combine(Arrayable|array $values): static
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

    #[\Override]
    public function union(Arrayable|array $items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function nth(int $step, int $offset = 0): static
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

    #[\Override]
    public function only(Enumerable|array|string $keys): static
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

    #[\Override]
    public function concat(array $source): static
    {
        return (new static(function () use ($source) {
            yield from $this;
            yield from $source;
        }))->values();
    }

    #[\Override]
    public function random(int|callable|null $number = null): static
    {
        if (is_null($number)) {
            return $this->take(1);
        }

        $resolved = is_callable($number) ? $number($this->collect()) : $number;

        return new static($this->collect()->random($resolved)->all());
    }

    #[\Override]
    public function replace(Arrayable|array $items): static
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

    #[\Override]
    public function replaceRecursive(Arrayable|array $items): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function reverse(): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function search(mixed $value, bool $strict = false): int|string|false
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

    #[\Override]
    public function before(mixed $value, bool $strict = false): mixed
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

    #[\Override]
    public function after(mixed $value, bool $strict = false): mixed
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

    #[\Override]
    public function shuffle(): static
    {
        return $this->passthru(__FUNCTION__, []);
    }

    #[\Override]
    public function sliding(int $size = 2, int $step = 1): static
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

    #[\Override]
    public function skip(int $count): static
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

    #[\Override]
    public function skipUntil(callable $value): static
    {
        return $this->skipWhile($this->negate($value));
    }

    #[\Override]
    public function skipWhile(callable $value): static
    {
        return new static(function () use ($value) {
            $iterator = $this->getIterator();

            while ($iterator->valid() && $value($iterator->current(), $iterator->key())) {
                $iterator->next();
            }

            while ($iterator->valid()) {
                yield $iterator->key() => $iterator->current();
                $iterator->next();
            }
        });
    }

    #[\Override]
    public function slice(int $offset, ?int $length = null): static
    {
        if ($offset < 0 || (! is_null($length) && $length < 0)) {
            return $this->passthru(__FUNCTION__, func_get_args());
        }

        $instance = $this->skip($offset);
        return is_null($length) ? $instance : $instance->take($length);
    }

    #[\Override]
    public function split(int $numberOfGroups): static
    {
        if ($numberOfGroups < 1) {
            throw new InvalidArgumentException('Number of groups must be at least 1.');
        }

        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function sole(callable|string|null $key = null, mixed $operator = null, mixed $value = null)
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

    #[\Override]
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

    #[\Override]
    public function firstOrFail(callable|string|null $key = null, mixed $operator = null, mixed $value = null)
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

    #[\Override]
    public function chunk(int $size, bool $preserveKeys): static
    {
        if ($size <= 0) {
            return static::empty();
        }

        return new static(function () use ($size) {
            $iterator = $this->getIterator();

            while ($iterator->valid()) {
                $chunk = [];

                while (true) {
                    $chunk[$iterator->key()] = $iterator->current();

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

    #[\Override]
    public function splitIn(int $numberOfGroups): static
    {
        if ($numberOfGroups < 1) {
            throw new InvalidArgumentException('Number of groups must be at least 1.');
        }

        return $this->chunk((int) ceil($this->count() / $numberOfGroups));
    }

    #[\Override]
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

    #[\Override]
    public function sort(callable|int|null $callback = null): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function sortDesc(int $options = SORT_REGULAR): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function sortBy(array|callable|int|string $callback, int $options = SORT_REGULAR, bool $descending = false): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function sortByDesc(array|callable|int|string $callback, int $options = SORT_REGULAR): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function sortKeysDesc(int $options = SORT_REGULAR): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function sortKeysUsing(callable $callback): static
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    #[\Override]
    public function take(int $limit): static
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

    #[\Override]
    public function takeUntil(callable $value): static
    {
        return new static(function () use ($value) {
            foreach ($this as $key => $item) {
                if ($value($item, $key)) {
                    break;
                }

                yield $key => $item;
            }
        });
    }

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

    #[\Override]
    public function takeWhile(callable $value): static
    {
        return $this->takeUntil(fn ($item, $key) => ! $value($item, $key));
    }

    public function tapEach(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                $callback($value, $key);
                yield $key => $value;
            }
        });
    }

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

    public function dot($depth = INF): static
    {
        return $this->passthru(__FUNCTION__, [$depth]);
    }

    #[\Override]
    public function undot(): static
    {
        return $this->passthru(__FUNCTION__, []);
    }

    #[\Override]
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

    #[\Override]
    public function values(): static
    {
        return new static(function () {
            foreach ($this as $item) {
                yield $item;
            }
        });
    }

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

    protected function intervalSeconds(DateInterval $interval): int
    {
        $start = new DateTimeImmutable();
        return $start->add($interval)->getTimestamp() - $start->getTimestamp();
    }

    #[\Override]
    public function zip(mixed ...$items): static
    {
        $iterables = $items;

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

    #[\Override]
    public function pad(int $size, $value): static
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

    #[\Override]
    public function getIterator(): Traversable
    {
        return $this->makeIterator($this->source);
    }

    #[\Override]
    public function count(): int
    {
        if (is_array($this->source)) {
            return count($this->source);
        }

        return iterator_count($this->getIterator());
    }

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

    protected function explodePluckParameters(array|string $value, ?string $key): array
    {
        $value = is_string($value) ? explode('.', $value) : $value;
        $key = is_null($key) || is_array($key) || $key instanceof Closure ? $key : explode('.', $key);
        return [$value, $key];
    }

    protected function passthru(string $method, array $params): static
    {
        return new static(function () use ($method, $params) {
            yield from $this->collect()->$method(...$params);
        });
    }

    protected function now(): int
    {
        return class_exists(Carbon::class)
            ? Carbon::now()->getTimestamp()
            : time();
    }

    protected function preciseNow(): float
    {
        return class_exists(Carbon::class)
            ? Carbon::now()->getPreciseTimestamp()
            : microtime(true) * 1_000_000;
    }

    protected function usleep(int $microseconds): void
    {
        if ($microseconds <= 0) {
            return;
        }

        usleep($microseconds);
    }
}
