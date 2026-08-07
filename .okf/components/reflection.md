---
type: Module
title: reflection
description: fabricate/reflection — Reflector utilities, ReflectsClosures, and the global lazy() helper.
resource: src/Fabricate/Reflection/
tags: [component, reflection]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T21:08:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-06T21:05:00Z" }
status: stable
sources:
  - id: composer
    resource: src/Fabricate/Reflection/composer.json
    title: fabricate/reflection manifest
  - id: reflector
    resource: src/Fabricate/Reflection/Reflector.php
    title: Reflector class
  - id: closures
    resource: src/Fabricate/Reflection/Concerns/ReflectsClosures.php
    title: ReflectsClosures trait
  - id: helpers
    resource: src/Fabricate/Reflection/Helpers/helpers.php
    title: lazy() helper
  - id: root
    resource: composer.json
    title: Umbrella autoload.files
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/reflection`[^composer] |
| Path | `src/Fabricate/Reflection/` |
| PHP namespace | `Fabricate\NutsAndBolts\` / `Concerns\` (umbrella multi-path PSR-4)[^root] |
| Umbrella | `replace` → `self.version` |

# How it works

## `Reflector`

Static helpers around PHP reflection:[^reflector]

- Callable checks that understand `[Class, 'method']` / `__call` / `__callStatic`
- Class attribute lookup (`getClassAttribute` / `getClassAttributes`), optional inheritance walk
- Named / union / enum type helpers used by the framework’s DI-ish and attribute code paths

## `ReflectsClosures`

Trait that introspects a Closure’s first parameter type (and related) — used by `lazy()` to decide which class to ghost when you pass a typed Closure.[^closures][^helpers]

## `lazy()` helper

When `Helpers/helpers.php` is loaded, global `lazy()` builds a PHP **lazy ghost** (or related lazy object) for a class, initializing it via callback on first property access.[^helpers]

# Autoload gap

Component composer lists `files: ["Helpers/helpers.php"]`.[^composer] Umbrella root `autoload.files` currently loads **only** Collections helpers — not Reflection’s file.[^root] So `scrapyard-io/framework` alone may not register `lazy()` until that file is added to the umbrella autoload (or something else loads it).

# Composer note

Manifest requires `fabricate/collection` (singular) `^0.6|^0.7` — package name in replace is `fabricate/collections` (plural).[^composer]

# Surface

| Symbol | Role |
|--------|------|
| `Reflector` | Callable / attribute / type reflection |
| `Concerns\ReflectsClosures` | Closure parameter types |
| `Helpers/helpers.php` | Global `lazy()` when loaded |

# Related

- [collections](collections.md) (uses reflection-adjacent patterns; separate component)
- [Component packages](../conventions/component-packages.md)

[^composer]: fabricate/reflection manifest
[^reflector]: Reflector class
[^closures]: ReflectsClosures trait
[^helpers]: lazy() helper
[^root]: Umbrella autoload.files
