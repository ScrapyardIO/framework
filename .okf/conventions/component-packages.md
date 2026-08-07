---
type: Convention
title: Component packages
description: Each Fabricate component directory may ship fabricate/* and is replaced by the umbrella; OKF stays at package root only.
tags: [convention, components, composer]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T21:08:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: nab
    resource: src/Fabricate/NutsAndBolts/composer.json
    title: fabricate/nuts-and-bolts
  - id: collections
    resource: src/Fabricate/Collections/composer.json
    title: fabricate/collections
  - id: conditionable
    resource: src/Fabricate/Conditionable/composer.json
    title: fabricate/conditionable
  - id: macroable
    resource: src/Fabricate/Macroable/composer.json
    title: fabricate/macroable
  - id: reflection
    resource: src/Fabricate/Reflection/composer.json
    title: fabricate/reflection
  - id: root
    resource: composer.json
    title: Umbrella replace + autoload
---

# Current replace set

| Composer name | Directory |
|---------------|-----------|
| `fabricate/nuts-and-bolts` | `src/Fabricate/NutsAndBolts/`[^nab] |
| `fabricate/collections` | `src/Fabricate/Collections/`[^collections] |
| `fabricate/conditionable` | `src/Fabricate/Conditionable/`[^conditionable] |
| `fabricate/macroable` | `src/Fabricate/Macroable/`[^macroable] |
| `fabricate/reflection` | `src/Fabricate/Reflection/`[^reflection] |
| `fabricate/bus` | `src/Fabricate/Bus/` |
| `fabricate/encryption` | `src/Fabricate/Encryption/` |
| `fabricate/hashing` | `src/Fabricate/Hashing/` |
| `fabricate/http` | `src/Fabricate/Http/` |
| `fabricate/json-schema` | `src/Fabricate/JsonSchema/` |
| `fabricate/queue` | `src/Fabricate/Queue/` |

Prefer `composer require scrapyard-io/framework`. Do not also require the split packages when using the umbrella.[^root]

As more components return, this table grows. Not every component will use `Fabricate\NutsAndBolts\`.

# Placement

- Knowledge in package-root `.okf/` only
- No nested `.okf/` under `src/Fabricate/*`

# Related

- [components/](../components/index.md)
- [Composer replace](composer-replace.md)

[^nab]: fabricate/nuts-and-bolts
[^collections]: fabricate/collections
[^conditionable]: fabricate/conditionable
[^macroable]: fabricate/macroable
[^reflection]: fabricate/reflection
[^root]: Umbrella replace + autoload
