---
type: Module
title: collections
description: fabricate/collections — eager/lazy enumerables, Arr, proxies, and collect/data_* helpers.
resource: src/Fabricate/Collections/
tags: [component, collections]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T21:08:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-06T21:05:00Z" }
status: stable
sources:
  - id: composer
    resource: src/Fabricate/Collections/composer.json
    title: fabricate/collections manifest
  - id: root
    resource: composer.json
    title: Root files autoload + replace
  - id: collection
    resource: src/Fabricate/Collections/Collection.php
    title: Collection class
  - id: lazy
    resource: src/Fabricate/Collections/LazyCollection.php
    title: LazyCollection class
  - id: enumerates
    resource: src/Fabricate/Collections/Concerns/EnumeratesValues.php
    title: Shared enumerable behavior + proxies
  - id: enumerable
    resource: src/Fabricate/Collections/Contracts/Enumerable.php
    title: Enumerable contract
  - id: proxy
    resource: src/Fabricate/Collections/HigherOrderCollectionProxy.php
    title: Higher-order collection proxy
  - id: helpers
    resource: src/Fabricate/Collections/Helpers/helpers.php
    title: Global helpers
  - id: functions
    resource: src/Fabricate/Collections/Helpers/functions.php
    title: Namespaced enum_value
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/collections`[^composer] |
| Path | `src/Fabricate/Collections/` |
| PHP namespace | Types declare `Fabricate\NutsAndBolts\` (+ `Concerns`, `Contracts`, `Exceptions`, `Helpers`)[^root] |
| Umbrella | `replace` → `self.version`; root also `files`-autoloads both helper files |

# How it works

## Mental model

Fluent, Laravel-shaped **enumerable value object** over keyed items. Build → chain transforms → terminate to scalar / array / JSON.[^enumerable][^enumerates]

| Type | Storage | When work runs |
|------|---------|----------------|
| `Collection` | In-memory `array $items` | Eager |
| `LazyCollection` | Closure / array / nested lazy `source` | Deferred until `all()`, `count()`, etc.[^lazy] |

`Collection::lazy()` wraps the eager array as a `LazyCollection`.[^collection]

```php
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\LazyCollection;

$eager = collect([1, 2, 3])->map(fn ($n) => $n * 2);
$lazy  = LazyCollection::make(fn () => yield from range(1, 1_000_000))
    ->map(fn ($n) => $n * 2);
```

## Entry points

| Entry | Result |
|-------|--------|
| `collect($value)` | Global → `Collection`[^helpers] |
| `Collection::make` / `wrap` / `times` / `range` / `empty` / `fromJson` | Static constructors[^enumerates] |
| `new Collection($items)` | Via `getArrayableItems` → `Arr::wrap` / `Arr::from` |
| `new LazyCollection($source)` | Closure factory, array, Arrayable, or lazy — **not** a bare `Generator`[^lazy] |

## Shared pipeline (`EnumeratesValues`)

Most fluent API lives here (both eager and lazy):[^enumerates]

- Transforms → new enumerable (`map`, `filter`, `pluck`, …)
- Terminals → leave enumerable world (`all`, `first`, `sum`, `toJson`, …)
- Pulls in [conditionable](conditionable.md) (`when` / `unless` / emptiness helpers)
- Uses [macroable](macroable.md) for `Collection::macro` / `mixin`
- Uses [nuts-and-bolts](nuts-and-bolts.md) contracts (`Arrayable`, `Jsonable`, …)
- Normalizes enums with `enum_value()`[^functions]

`Collection` also implements `ArrayAccess` + `TransformsToResourceCollection`. Lazy does not.

## Higher-order proxies

Property access on a proxied method name returns `HigherOrderCollectionProxy`:[^proxy]

```php
collect([$a, $b])->filter->active->map->name;
```

Register more with `Enumerable::proxy('methodName')`.

## `Arr`

Static array toolkit (also Macroable). Prefer `collect()` for chains; `Arr::*` for plain arrays.

## Failures

- `ItemNotFoundException`
- `MultipleItemsFoundException`

# Requires

```json
"fabricate/macroable": "^0.6.0",
"fabricate/nuts-and-bolts": "^0.6.0"
```

No `fabricate/contracts`. Component `files` lists `helpers.php` only; umbrella also loads `functions.php`.[^composer][^root]

# Surface

| Symbol | Role |
|--------|------|
| `Collection` / `LazyCollection` | Eager / deferred enumerables |
| `Arr` | Static arrays |
| `Contracts\Enumerable` | Shared contract |
| `Concerns\EnumeratesValues` | Fluent core + proxies |
| `Concerns\TransformsToResourceCollection` | Eager resource transform |
| `HigherOrderCollectionProxy` | `$c->map->attr` |
| Exceptions | Strict find failures |
| `Helpers/*` | `collect`, `data_*`, `enum_value`, … |

# Related

- [Use collect helpers](../playbooks/use-collect-helpers.md)
- [macroable](macroable.md), [conditionable](conditionable.md), [nuts-and-bolts](nuts-and-bolts.md)

[^composer]: fabricate/collections manifest
[^root]: Root files autoload + replace
[^collection]: Collection class
[^lazy]: LazyCollection class
[^enumerates]: Shared enumerable behavior + proxies
[^enumerable]: Enumerable contract
[^proxy]: Higher-order collection proxy
[^helpers]: Global helpers
[^functions]: Namespaced enum_value
