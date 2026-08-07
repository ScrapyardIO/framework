---
type: Module
title: conditionable
description: fabricate/conditionable — conditional method chaining via when/unless and HigherOrderWhenProxy.
resource: src/Fabricate/Conditionable/
tags: [component, conditionable]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T21:08:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-06T21:05:00Z" }
status: stable
sources:
  - id: composer
    resource: src/Fabricate/Conditionable/composer.json
    title: fabricate/conditionable manifest
  - id: trait
    resource: src/Fabricate/Conditionable/Concerns/Conditionable.php
    title: Conditionable trait
  - id: proxy
    resource: src/Fabricate/Conditionable/HigherOrderWhenProxy.php
    title: HigherOrderWhenProxy
  - id: root
    resource: composer.json
    title: Umbrella autoload / replace
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/conditionable`[^composer] |
| Path | `src/Fabricate/Conditionable/` |
| PHP namespace | Types declare `Fabricate\NutsAndBolts\` / `Concerns\` (umbrella multi-path PSR-4)[^root] |
| Requires | PHP only |
| Umbrella | `replace` → `self.version` |

# How it works

`Conditionable` is a trait you mix into any class to get fluent **conditional callbacks**:[^trait]

| Call shape | Behavior |
|------------|----------|
| `when($value, $callback, $default?)` | If `$value` (or Closure result) is truthy, run `$callback($this, $value)`; else optional `$default` |
| `unless(...)` | Same with inverted condition |
| `when()` / `when($value)` with fewer args | Returns `HigherOrderWhenProxy` for property/method proxying[^proxy] |

Closures passed as the condition receive `$this`. Callbacks that return `null` fall through to returning `$this` (chain continues).

```php
use Fabricate\NutsAndBolts\Concerns\Conditionable;

class Pipeline
{
    use Conditionable;
}

$pipeline
    ->when($debug, fn ($p) => $p->enableLogging())
    ->unless($dryRun, fn ($p) => $p->commit());
```

Collections pull this in via `EnumeratesValues` (`when` / `unless` / `whenEmpty` / …).[^trait]

# Surface

| Symbol | Role |
|--------|------|
| `Concerns\Conditionable` | `when` / `unless` |
| `HigherOrderWhenProxy` | Partial-application proxy |

# Related

- [collections](collections.md) (consumer)
- [macroable](macroable.md)

[^composer]: fabricate/conditionable manifest
[^trait]: Conditionable trait
[^proxy]: HigherOrderWhenProxy
[^root]: Umbrella autoload / replace
