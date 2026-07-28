<?php

namespace Fabricate\NutsAndBolts\Contracts;

use Countable;
use Exception;
use Traversable;
use CachingIterator;
use IteratorAggregate;
use JsonSerializable;
use InvalidArgumentException;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\Contracts\NutsAndBolts\Jsonable;
use Fabricate\Contracts\NutsAndBolts\Arrayable;
use Fabricate\NutsAndBolts\Exceptions\ItemNotFoundException;
use Fabricate\NutsAndBolts\Exceptions\MultipleItemsFoundException;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @extends Arrayable<TKey, TValue>
 * @extends IteratorAggregate<TKey, TValue>
 */
interface Enumerable extends Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @template TMakeKey of array-key
     * @template TMakeValue
     */
    public static function make(Arrayable|iterable|null $items = [], mixed ...$args): static;
    /**
     * Create a new instance by invoking the callback a given amount of times.
     *
     * @template TTimesValue
     */
    public static function times(int $number, ?callable $callback = null, mixed ...$args): static;

    /**
     * Create a collection with the given range.
     */
    public static function range(int|float|string $from, int|float|string $to, int|float $step = 1, mixed ...$args): static;

    /**
     * Wrap the given value in a collection if applicable.
     *
     * @template TWrapValue
     */
    public static function wrap(iterable $value, mixed ...$args): static;

    /**
     * Get the underlying items from the given collection if applicable.
     *
     * @template TUnwrapKey of array-key
     * @template TUnwrapValue
     */
    public static function unwrap(array|Enumerable $value): array;

    /**
     * Create a new instance with no items.
     */
    public static function empty(mixed ...$args): static;
    /**
     * Get all items in the enumerable.
     */
    public function all(): array;

    /**
     * Alias for the "avg" method.
     */
    public function average(callable|string|null $callback = null): float|int|null;

    /**
     * Get the median of a given key.
     */
    public function median(string|array|null $key = null): float|int|null;

    /**
     * Get the mode of a given key.
     */
    public function mode(string|array|null $key = null): ?array;

    /**
     * Collapse the items into a single enumerable.
     */
    public function collapse(): static;

    /**
     * Alias for the "contains" method.
     */
    public function some(callable|string $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Determine if an item exists, using strict comparison.
     *
     * @param  (callable(TValue): bool)|TValue|array-key  $key
     * @param  TValue|null  $value
     */
    public function containsStrict($key, mixed $value = null): bool;

    /**
     * Get the average value of a given key.
     */
    public function avg(callable|string|null $callback = null): float|int|null;

    /**
     * Determine if an item exists in the enumerable.
     *
     * @param  (callable(TValue, TKey): bool)|TValue|string  $key
     */
    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Determine if an item is not contained in the collection.
     */
    public function doesntContain(mixed $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Cross join with the given lists, returning all possible permutations.
     *
     * @template TCrossJoinKey
     * @template TCrossJoinValue
     */
    public function crossJoin(...$lists): static;

    /**
     * Dump the collection and end the script.
     */
    public function dd(mixed ...$args): never;

    /**
     * Dump the collection.
     */
    public function dump(mixed ...$args): static;

    /**
     * Get the items that are not present in the given items.
     *
     * @param  Arrayable<array-key, TValue>|iterable<array-key, TValue>  $items
     */
    public function diff($items): static;

    /**
     * Get the items that are not present in the given items, using the callback.
     *
     * @param  Arrayable<array-key, TValue>|iterable<array-key, TValue>  $items
     * @param  callable(TValue, TValue): int  $callback
     */
    public function diffUsing($items, callable $callback): static;

    /**
     * Get the items whose keys and values are not present in the given items.
     *
     * @param  Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
     * @return static
     */
    public function diffAssoc(Arrayable|iterable $items): static;

    /**
     * Get the items whose keys and values are not present in the given items, using the callback.
     *
     * @param  Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
     * @param  callable(TKey, TKey): int  $callback
     * @return static
     */
    public function diffAssocUsing(Arrayable|iterable $items, callable $callback): static;

    /**
     * Get the items whose keys are not present in the given items.
     *
     * @param  Arrayable<TKey, mixed>|iterable<TKey, mixed>  $items
     * @return static
     */
    public function diffKeys(Arrayable|iterable $items): static;

    /**
     * Get the items whose keys are not present in the given items, using the callback.
     *
     * @param  Arrayable<TKey, mixed>|iterable<TKey, mixed>  $items
     * @param  callable(TKey, TKey): int  $callback
     * @return static
     */
    public function diffKeysUsing(Arrayable|array $items, callable $callback): static;

    /**
     * Retrieve duplicate items.
     *
     * @param  (callable(TValue): bool)|string|null  $callback
     * @param bool $strict
     * @return static
     */
    public function duplicates(callable|string|null $callback = null, bool $strict = false): static;

    /**
     * Retrieve duplicate items using strict comparison.
     *
     * @param (callable(TValue): bool)|string|null $callback
     * @return static
     */
    public function duplicatesStrict(callable|string|null $callback = null): static;

    /**
     * Execute a callback over each item.
     */
    public function each(callable $callback): static;

    /**
     * Execute a callback over each nested chunk of items.
     */
    public function eachSpread(callable $callback): static;

    /**
     * Determine if all items pass the given truth test.
     */
    public function every(callable|string $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Get all items except for those with the specified keys.
     *
     * @param Enumerable<array-key, TKey>|array<array-key, TKey> $keys
     * @return static
     */
    public function except(Enumerable|array $keys): static;

    /**
     * Run a filter over each of the items.
     *
     * @param  (callable(TValue): bool)|null  $callback
     * @return static
     */
    public function filter(?callable $callback = null): static;

    /**
     * Apply the callback if the given "value" is (or resolves to) truthy.
     *
     * @template TWhenReturnType as null
     *
     * @param  bool  $value
     * @param  (callable($this): TWhenReturnType)|null  $callback
     * @param  (callable($this): TWhenReturnType)|null  $default
     * @return $this|TWhenReturnType
     */
    public function when(mixed $value = null, ?callable $callback = null, ?callable $default = null): mixed;

    /**
     * Apply the callback if the collection is empty.
     *
     * @template TWhenEmptyReturnType
     */
    public function whenEmpty(callable $callback, ?callable $default = null): static;

    /**
     * Apply the callback if the collection is not empty.
     *
     * @template TWhenNotEmptyReturnType
     */
    public function whenNotEmpty(callable $callback, ?callable $default = null): static;

    /**
     * Apply the callback if the given "value" is (or resolves to) falsy.
     *
     * @template TUnlessReturnType
     *
     * @param  bool  $value
     * @param  (callable($this): TUnlessReturnType)  $callback
     * @param  (callable($this): TUnlessReturnType)|null  $default
     * @return $this|TUnlessReturnType
     */
    public function unless(mixed $value = null, ?callable $callback = null, ?callable $default = null): mixed;

    /**
     * Apply the callback unless the collection is empty.
     *
     * @template TUnlessEmptyReturnType
     */
    public function unlessEmpty(callable $callback, ?callable $default = null): static;

    /**
     * Apply the callback unless the collection is not empty.
     *
     * @template TUnlessNotEmptyReturnType
     */
    public function unlessNotEmpty(callable $callback, ?callable $default = null): static;

    /**
     * Filter items by the given key value pair.
     */
    public function where(callable|string $key, mixed $operator = null, mixed $value = null): static;

    /**
     * Filter items where the value for the given key is null.
     */
    public function whereNull(?string $key = null): static;

    /**
     * Filter items where the value for the given key is not null.
     */
    public function whereNotNull(?string $key = null): static;

    /**
     * Filter items by the given key value pair using strict comparison.
     */
    public function whereStrict(string $key, mixed $value): static;

    /**
     * Filter items by the given key value pair.
     */
    public function whereIn(string $key, Arrayable|iterable $values, bool $strict = false): static;

    /**
     * Filter items by the given key value pair using strict comparison.
     */
    public function whereInStrict(string $key, Arrayable|iterable $values): static;

    /**
     * Filter items such that the value of the given key is between the given values.
     */
    public function whereBetween(string $key, Arrayable|iterable $values): static;

    /**
     * Filter items such that the value of the given key is not between the given values.
     */
    public function whereNotBetween(string $key, Arrayable|iterable $values): static;

    /**
     * Filter items by the given key value pair.
     */
    public function whereNotIn(string $key, Arrayable|iterable $values, bool $strict = false): static;

    /**
     * Filter items by the given key value pair using strict comparison.
     */
    public function whereNotInStrict(string $key, Arrayable|iterable $values): static;

    /**
     * Filter the items, removing any items that don't match the given type(s).
     *
     * @template TWhereInstanceOf
     */
    public function whereInstanceOf(string|array $type): static;

    /**
     * Get the first item from the enumerable passing the given truth test.
     *
     * @template TFirstDefault
     *
     * @param  (callable(TValue,TKey): bool)|null  $callback
     * @param (callable(): TFirstDefault)|null $default
     * @return TValue|TFirstDefault
     */
    public function first(?callable $callback = null, mixed $default = null): mixed;

    /**
     * Get the first item by the given key value pair.
     */
    public function firstWhere(callable|string $key, mixed $operator = null, mixed $value = null): mixed;

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param float|int $depth
     * @return static<int, mixed>
     */
    public function flatten(float|int $depth = INF): static;

    /**
     * Flip the values with their keys.
     *
     * @return static<TValue, TKey>
     */
    public function flip(): static;

    /**
     * Get an item from the collection by key.
     *
     * @template TGetDefault
     *
     * @param TKey $key
     * @param (callable(): TGetDefault)|null $default
     * @return TValue|TGetDefault
     */
    public function get(int|string $key, mixed $default = null): mixed;

    /**
     * Group an associative array by a field or using a callback.
     *
     * @template TGroupKey of array-key|\UnitEnum|\Stringable
     *
     * @param (callable(TValue, TKey): TGroupKey)|array|string $groupBy
     * @param bool $preserveKeys
     * @return static<
     *  ($groupBy is (array|string)
     *      ? array-key
     *      : (TGroupKey is \UnitEnum ? array-key : (TGroupKey is \Stringable ? string : TGroupKey))),
     *  static<($preserveKeys is true ? TKey : int), ($groupBy is array ? mixed : TValue)>
     * >
     */
    public function groupBy(callable|array|string $groupBy, bool $preserveKeys = false): static;

    /**
     * Key an associative array by a field or using a callback.
     *
     * @template TNewKey of array-key|\UnitEnum
     *
     * @param (callable(TValue, TKey): TNewKey)|array|string $keyBy
     * @return static<($keyBy is (array|string) ? array-key : (TNewKey is \UnitEnum ? array-key : TNewKey)), TValue>
     */
    public function keyBy(callable|array|string $keyBy): static;

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param TKey|array<array-key, TKey> $key
     * @return bool
     */
    public function has(array|int|string $key): bool;

    /**
     * Determine if any of the keys exist in the collection.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function hasAny(mixed $key): bool;

    /**
     * Concatenate values of a given key as a string.
     *
     * @param (callable(TValue, TKey): mixed)|string $value
     * @param string|null $glue
     * @return string
     */
    public function implode(callable|string $value, ?string $glue = null): string;

    /**
     * Intersect the collection with the given items.
     *
     * @param Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
     * @return static
     */
    public function intersect(Arrayable|array $items): static;

    /**
     * Intersect the collection with the given items, using the callback.
     *
     * @param Arrayable<array-key, TValue>|iterable<array-key, TValue> $items
     * @param  callable(TValue, TValue): int  $callback
     * @return static
     */
    public function intersectUsing(Arrayable|array $items, callable $callback): static;

    /**
     * Intersect the collection with the given items with additional index check.
     *
     * @param Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
     * @return static
     */
    public function intersectAssoc(Arrayable|array $items): static;

    /**
     * Intersect the collection with the given items with additional index check, using the callback.
     *
     * @param Arrayable<array-key, TValue>|iterable<array-key, TValue> $items
     * @param  callable(TValue, TValue): int  $callback
     * @return static
     */
    public function intersectAssocUsing(Arrayable|array $items, callable $callback): static;

    /**
     * Intersect the collection with the given items by key.
     *
     * @param Arrayable<TKey, mixed>|iterable<TKey, mixed> $items
     * @return static
     */
    public function intersectByKeys(array|Arrayable $items): static;

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Determine if the collection is not empty.
     */
    public function isNotEmpty(): bool;

    /**
     * Determine if the collection contains a single item.
     *
     * @deprecated Use the `hasSole()` method instead.
     */
    public function containsOneItem(?callable $callback = null): bool;

    /**
     * Determine if the collection contains multiple items.
     *
     * @deprecated Use the `hasMany()` method instead.
     */
    public function containsManyItems(?callable $callback = null): bool;

    /**
     * Determine if the collection contains a single item, optionally matching the given criteria.
     */
    public function hasSole($key = null, $operator = null, $value = null): bool;

    /**
     * Determine if the collection contains multiple items, optionally matching the given criteria.
     */
    public function hasMany(callable|string|null $key = null, mixed $operator = null, mixed $value = null): bool;

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
     *
     * @param string $glue
     * @param string $finalGlue
     * @return string
     */
    public function join(string $glue, string $finalGlue = ''): string;

    /**
     * Get the keys of the collection items.
     *
     * @return static<int, TKey>
     */
    public function keys(): static;

    /**
     * Get the last item from the collection.
     *
     * @template TLastDefault
     *
     * @param  (callable(TValue, TKey): bool)|null  $callback
     * @param (callable(): TLastDefault)|null $default
     * @return TValue|TLastDefault
     */
    public function last(?callable $callback = null, mixed $default = null): mixed;

    /**
     * Run a map over each of the items.
     *
     * @template TMapValue
     *
     * @param  callable(TValue, TKey): TMapValue  $callback
     * @return static<TKey, TMapValue>
     */
    public function map(callable $callback): static;

    /**
     * Run a map over each nested chunk of items.
     *
     * @template TMapSpreadValue
     */
    public function mapSpread(callable $callback): static;

    /**
     * Run a dictionary map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapToDictionaryKey of array-key
     * @template TMapToDictionaryValue
     *
     * @param  callable(TValue, TKey): array<TMapToDictionaryKey, TMapToDictionaryValue>  $callback
     * @return static<TMapToDictionaryKey, array<int, TMapToDictionaryValue>>
     */
    public function mapToDictionary(callable $callback): static;

    /**
     * Run a grouping map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapToGroupsKey of array-key
     * @template TMapToGroupsValue
     */
    public function mapToGroups(callable $callback): static;

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
     * @return static<TMapWithKeysKey, TMapWithKeysValue>
     */
    public function mapWithKeys(callable $callback): static;

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @template TFlatMapKey of array-key
     * @template TFlatMapValue
     */
    public function flatMap(callable $callback): static;

    /**
     * Map the values into a new class.
     *
     * @template TMapIntoValue
     */
    public function mapInto(string $class): static;

    /**
     * Merge the collection with the given items.
     *
     * @template TMergeValue
     *
     * @param Arrayable<TKey, TMergeValue>|iterable<TKey, TMergeValue> $items
     * @return static<TKey, TValue|TMergeValue>
     */
    public function merge(Arrayable|array $items): static;

    /**
     * Recursively merge the collection with the given items.
     *
     * @template TMergeRecursiveValue
     *
     * @param Arrayable<TKey, TMergeRecursiveValue>|iterable<TKey, TMergeRecursiveValue> $items
     * @return static<TKey, TValue|TMergeRecursiveValue>
     */
    public function mergeRecursive(Arrayable|array $items): static;

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @template TCombineValue
     *
     * @param Arrayable<array-key, TCombineValue>|iterable<array-key, TCombineValue> $values
     * @return static<TValue, TCombineValue>
     */
    public function combine(Arrayable|array $values): static;

    /**
     * Union the collection with the given items.
     *
     * @param Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
     * @return static
     */
    public function union(Arrayable|array $items): static;

    /**
     * Get the min value of a given key.
     *
     * @template TMinResult = mixed
     */
    public function min(callable|string|null $callback = null): mixed;

    /**
     * Get the max value of a given key.
     *
     * @template TMaxResult = mixed
     */
    public function max(callable|string|null $callback = null): mixed;

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param int $step
     * @param int $offset
     * @return static
     */
    public function nth(int $step, int $offset = 0): static;

    /**
     * Get the items with the specified keys.
     *
     * @param string|Enumerable<array-key, TKey>|array<array-key, TKey> $keys
     * @return static
     */
    public function only(Enumerable|array|string $keys): static;

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     */
    public function forPage(int $page, int $perPage): static;

    /**
     * Partition the collection into two arrays using the given callback or key.
     */
    public function partition(mixed $key, mixed $operator = null, mixed $value = null): static;

    /**
     * Push every given item onto the collection.
     *
     * @template TConcatKey of array-key
     * @template TConcatValue
     *
     * @param iterable<TConcatKey, TConcatValue> $source
     * @return static<TKey|TConcatKey, TValue|TConcatValue>
     */
    public function concat(array $source): static;

    /**
     * Get one or a specified number of items randomly from the collection.
     *
     * @param int|null $number
     * @return ($number is null ? TValue : static<int, TValue>)
     *
     * @throws InvalidArgumentException
     */
    public function random(int|callable|null $number = null): static;

    /**
     * Reduce the collection to a single value.
     *
     * @template TReduceInitial
     * @template TReduceReturnType
     */
    public function reduce(callable $callback, mixed $initial = null): mixed;

    /**
     * Reduce the collection to multiple aggregate values.
     */
    public function reduceSpread(callable $callback, mixed ...$initial): array;

    /**
     * Replace the collection items with the given items.
     *
     * @param Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
     * @return static
     */
    public function replace(Arrayable|array $items): static;

    /**
     * Recursively replace the collection items with the given items.
     *
     * @param Arrayable<TKey, TValue>|iterable<TKey, TValue> $items
     * @return static
     */
    public function replaceRecursive(Arrayable|array $items): static;

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse(): static;

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param callable(TValue,TKey): bool $value
     * @param bool $strict
     * @return TKey|false
     */
    public function search(mixed $value, bool $strict = false): int|string|false;

    /**
     * Get the item before the given item.
     *
     * @param (callable(TValue,TKey): bool) $value
     * @param bool $strict
     * @return TValue|null
     */
    public function before(mixed $value, bool $strict = false): mixed;

    /**
     * Get the item after the given item.
     *
     * @param (callable(TValue,TKey): bool) $value
     * @param bool $strict
     * @return TValue|null
     */
    public function after(mixed $value, bool $strict = false): mixed;

    /**
     * Shuffle the items in the collection.
     *
     * @return static
     */
    public function shuffle(): static;

    /**
     * Create chunks representing a "sliding window" view of the items in the collection.
     *
     * @param int $size
     * @param int $step
     * @return static<int, static>
     */
    public function sliding(int $size = 2, int $step = 1): static;

    /**
     * Skip the first {$count} items.
     *
     * @param int $count
     * @return static
     */
    public function skip(int $count): static;

    /**
     * Skip items in the collection until the given condition is met.
     *
     * @param callable(TValue,TKey): bool $value
     * @return static
     */
    public function skipUntil(callable $value): static;

    /**
     * Skip items in the collection while the given condition is met.
     *
     * @param callable(TValue,TKey): bool $value
     * @return static
     */
    public function skipWhile(callable $value): static;

    /**
     * Get a slice of items from the enumerable.
     *
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice(int $offset, ?int $length = null): static;

    /**
     * Split a collection into a certain number of groups.
     *
     * @param int $numberOfGroups
     * @return static<int, static>
     */
    public function split(int $numberOfGroups): static;

    /**
     * Get the first item in the collection, but only if exactly one item exists. Otherwise, throw an exception.
     *
     * @param (callable(TValue, TKey): bool)|string|null $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return TValue
     *
     * @throws ItemNotFoundException
     * @throws MultipleItemsFoundException
     */
    public function sole(callable|string|null $key = null, mixed $operator = null, mixed $value = null);

    /**
     * Get the first item in the collection but throw an exception if no matching items exist.
     *
     * @param (callable(TValue, TKey): bool)|string|null $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return TValue
     *
     * @throws ItemNotFoundException
     */
    public function firstOrFail(callable|string|null $key = null, mixed $operator = null, mixed $value = null);

    /**
     * Chunk the collection into chunks of the given size.
     *
     * @param int $size
     * @return static<int, static>
     */
    public function chunk(int $size, bool $preserveKeys): static;

    /**
     * Chunk the collection into chunks with a callback.
     *
     * @param  callable(TValue, TKey, static<int, TValue>): bool  $callback
     * @return static<int, static<int, TValue>>
     */
    public function chunkWhile(callable $callback): static;

    /**
     * Split a collection into a certain number of groups, and fill the first groups completely.
     *
     * @param int $numberOfGroups
     * @return static<int, static>
     */
    public function splitIn(int $numberOfGroups): static;

    /**
     * Sort through each item with a callback.
     *
     * @param (callable(TValue, TValue): int)|int|null $callback
     * @return static
     */
    public function sort(callable|int|null $callback = null): static;

    /**
     * Sort items in descending order.
     *
     * @param int-mask-of<SORT_REGULAR|SORT_NUMERIC|SORT_STRING|SORT_LOCALE_STRING|SORT_NATURAL|SORT_FLAG_CASE> $options
     * @return static
     */
    public function sortDesc(int $options = SORT_REGULAR): static;

    /**
     * Sort the collection using the given callback.
     *
     * @param (callable(TValue, TKey): mixed)|int|string|array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, \SortDirection|'asc'|'desc'}> $callback
     * @param int-mask-of<SORT_REGULAR|SORT_NUMERIC|SORT_STRING|SORT_LOCALE_STRING|SORT_NATURAL|SORT_FLAG_CASE> $options
     * @param bool $descending
     * @return static
     */
    public function sortBy(array|callable|int|string $callback, int $options = SORT_REGULAR, bool $descending = false): static;

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @param (callable(TValue, TKey): mixed)|int|string|array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, \SortDirection|'asc'|'desc'}> $callback
     * @param int-mask-of<SORT_REGULAR|SORT_NUMERIC|SORT_STRING|SORT_LOCALE_STRING|SORT_NATURAL|SORT_FLAG_CASE> $options
     * @return static
     */
    public function sortByDesc(array|callable|int|string $callback, int $options = SORT_REGULAR): static;

    /**
     * Sort the collection keys.
     *
     * @param int-mask-of<SORT_REGULAR|SORT_NUMERIC|SORT_STRING|SORT_LOCALE_STRING|SORT_NATURAL|SORT_FLAG_CASE> $options
     * @param bool $descending
     * @return static
     */
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false): static;

    /**
     * Sort the collection keys in descending order.
     *
     * @param int-mask-of<SORT_REGULAR|SORT_NUMERIC|SORT_STRING|SORT_LOCALE_STRING|SORT_NATURAL|SORT_FLAG_CASE> $options
     * @return static
     */
    public function sortKeysDesc(int $options = SORT_REGULAR): static;

    /**
     * Sort the collection keys using a callback.
     *
     * @param  callable(TKey, TKey): int  $callback
     * @return static
     */
    public function sortKeysUsing(callable $callback): static;

    /**
     * Get the sum of the given values.
     *
     * @template TReturnType
     */
    public function sum(callable|string|null $callback = null): float|int|string;

    /**
     * Take the first or last {$limit} items.
     *
     * @param int $limit
     * @return static
     */
    public function take(int $limit): static;

    /**
     * Take items in the collection until the given condition is met.
     *
     * @param callable(TValue,TKey): bool $value
     * @return static
     */
    public function takeUntil(callable $value): static;

    /**
     * Take items in the collection while the given condition is met.
     *
     * @param callable(TValue,TKey): bool $value
     * @return static
     */
    public function takeWhile(callable $value): static;

    /**
     * Pass the collection to the given callback and then return it.
     */
    public function tap(callable $callback): static;

    /**
     * Pass the enumerable to the given callback and return the result.
     *
     * @template TPipeReturnType
     */
    public function pipe(callable $callback): mixed;

    /**
     * Pass the collection into a new class.
     *
     * @template TPipeIntoValue
     */
    public function pipeInto(string $class): mixed;

    /**
     * Pass the collection through a series of callable pipes and return the result.
     */
    public function pipeThrough(array $callbacks): mixed;

    /**
     * Get the values of a given key.
     *
     * @param string|array<array-key, string> $value
     * @param string|null $key
     * @return static<array-key, mixed>
     */
    public function pluck(array|string $value, ?string $key = null): static;

    /**
     * Create a collection of all elements that do not pass a given truth test.
     */
    public function reject(callable|bool $callback = true): static;

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     *
     * @return static
     */
    public function undot(): static;

    /**
     * Return only unique items from the collection array.
     */
    public function unique(callable|string|null $key = null, bool $strict = false): static;

    /**
     * Return only unique items from the collection array using strict comparison.
     */
    public function uniqueStrict(callable|string|null $key = null): static;

    /**
     * Reset the keys on the underlying array.
     *
     * @return static<int, TValue>
     */
    public function values(): static;

    /**
     * Pad collection to the specified length with a value.
     *
     * @template TPadValue
     *
     * @param int $size
     * @param  TPadValue  $value
     * @return static<int, TValue|TPadValue>
     */
    public function pad(int $size, $value): static;

    /**
     * Get the value iterator.
     *
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable;

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Count the number of items in the collection by a field or using a callback.
     *
     * @param (callable(TValue, TKey): (array-key|\UnitEnum))|string|null $countBy
     * @return static<array-key, int>
     */
    public function countBy(callable|string|null $countBy = null): static;

    /**
     * Zip the collection together with one or more arrays.
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @template TZipValue
     *
     * @param Arrayable<array-key, TZipValue> ...$items
     * @return static<int, static<int, TValue|TZipValue>>
     */
    public function zip(mixed ...$items): static;

    /**
     * Collect the values into a collection.
     */
    public function collect(): Collection;

    /**
     * Get the collection of items as a plain array.
     */
    public function toArray(): array;

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): array;

    /**
     * Get the collection of items as JSON.
     */
    public function toJson(int $options = 0): string;

    /**
     * Get the collection of items as pretty print formatted JSON.
     */
    public function toPrettyJson(int $options = 0): string;

    /**
     * Get a CachingIterator instance.
     */
    public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING): CachingIterator;

    /**
     * Convert the collection to its string representation.
     */
    public function __toString(): string;

    /**
     * Indicate that the model's string representation should be escaped when __toString is invoked.
     */
    public function escapeWhenCastingToString(bool $escape = true): static;

    /**
     * Add a method to the list of proxied methods.
     */
    public static function proxy(string $method): void;

    /**
     * Dynamically access collection proxies.
     *
     * @throws Exception
     */
    public function __get(string $key): mixed;
}
