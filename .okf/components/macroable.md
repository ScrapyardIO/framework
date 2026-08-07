---
type: Module
title: macroable
description: fabricate/macroable — runtime macros and mixins via the Macroable trait.
resource: src/Fabricate/Macroable/
tags: [component, macroable]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T21:08:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-06T21:05:00Z" }
status: stable
sources:
  - id: composer
    resource: src/Fabricate/Macroable/composer.json
    title: fabricate/macroable manifest
  - id: trait
    resource: src/Fabricate/Macroable/Concerns/Macroable.php
    title: Macroable trait
  - id: root
    resource: composer.json
    title: Umbrella autoload / replace
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/macroable`[^composer] |
| Path | `src/Fabricate/Macroable/` |
| PHP namespace | `Fabricate\NutsAndBolts\Concerns\Macroable` (umbrella multi-path PSR-4)[^root] |
| Requires | PHP only |
| Umbrella | `replace` → `self.version` |

# How it works

`Macroable` lets a class accept **runtime methods** without subclassing:[^trait]

| API | Role |
|-----|------|
| `macro($name, $callable)` | Register an instance/static method |
| `mixin($object, $replace = true)` | Register public/protected methods from another object as macros |
| `hasMacro` / `flushMacros` | Introspect / clear |
| `__call` / `__callStatic` | Dispatch registered macros; unknown → `BadMethodCallException` |

Closures bound for instance calls receive `$this`; static macros bind to the class.

```php
use Fabricate\NutsAndBolts\Concerns\Macroable;
use Fabricate\NutsAndBolts\Collection;

Collection::macro('sumOf', fn (string $key) => $this->sum($key));

collect([['n' => 1], ['n' => 2]])->sumOf('n'); // 3
```

`Collection`, `LazyCollection`, and `Arr` all use this trait.

# Surface

| Symbol | Role |
|--------|------|
| `Concerns\Macroable` | Macro / mixin / call dispatch |

# Related

- [collections](collections.md) (primary consumer)

[^composer]: fabricate/macroable manifest
[^trait]: Macroable trait
[^root]: Umbrella autoload / replace
