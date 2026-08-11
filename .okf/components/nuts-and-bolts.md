---
type: Module
title: nuts-and-bolts
description: fabricate/nuts-and-bolts — lowest Fabricate support component; contracts, utilities, helpers; must not depend on satellites or Core.
resource: src/Fabricate/NutsAndBolts/
tags: [component, nuts-and-bolts, support]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-10T20:40:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: composer
    resource: src/Fabricate/NutsAndBolts/composer.json
    title: fabricate/nuts-and-bolts manifest
  - id: manager
    resource: src/Fabricate/NutsAndBolts/Manager.php
    title: Manager (driver resolver)
  - id: splices16
    resource: src/Fabricate/NutsAndBolts/Concerns/Splices16Bits.php
    title: Splices16Bits concern
  - id: contracts
    resource: src/Fabricate/NutsAndBolts/Contracts/
    title: Contract interfaces
  - id: helpers
    resource: src/Fabricate/NutsAndBolts/Helpers/
    title: Helper files
  - id: defer
    resource: src/Fabricate/NutsAndBolts/Defer/
    title: Defer types
  - id: root
    resource: composer.json
    title: Umbrella replace
  - id: dep
    resource: .okf/conventions/dependency-direction.md
    title: Dependency direction rule
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/nuts-and-bolts`[^composer] |
| Path | `src/Fabricate/NutsAndBolts/` |
| PHP namespace | `Fabricate\NutsAndBolts\` |
| Umbrella | `replace` → `self.version` |
| Layer role | Support clique floor — **moons only**; must not know Core or other components[^dep] |

May depend on Collections, Conditionable, Macroable, Reflection (moons). Must **not** depend on Filesystem, Broadcasting, Config, etc. Core may depend on Nab.

# How it works

NutsAndBolts is the **pure support** floor: types and helpers that remain meaningful without a booted Machine.

## On disk (growing)

| Area | Examples |
|------|----------|
| Contracts | `Arrayable`, `Jsonable`, `CanBeEscapedWhenCastToString`, `DeferrableProvider`[^contracts] |
| Exception | `ScrapyardIOException` lives on `Fabricate\Contracts\Core` (not Nab root) — companions should import that FQCN |
| Utilities | `Str`, `Bytes`, `Carbon`, `Manager` (driver manager; Chassis `WireframeServiceContainer`), `MultipleInstanceManager`, … |
| Concerns | e.g. `Dumpable`, `RebindsCallbacksToSelf`, `Splices16Bits`, `Splices4Bits` |
| Defer types | `Defer\DeferredCallback`, `Defer\DeferredCallbackCollection`[^defer] |
| Helpers | `helpers.php` (`tap`, `env`, `with`, …), `bytes.php`, `time.php`, `functions.php`[^helpers] |

Satellites and other components type-hint Nab contracts and call Nab helpers. Core is where container-aware wiring belongs (`app()`, Workshop binary, path helpers, …).

# Dependency rule (target)

See [Component dependency direction](../conventions/dependency-direction.md).[^dep]

# Known violations (still draft)

Moon deps are fine (`fabricate/collections|macroable`, Defer → `Collection`). Still broken:

- **`Env.php`** — `writeVariable(s)` + Filesystem helpers **commented out** (flagged for relocation to Broadcasting/Core per [dependency-direction](../conventions/dependency-direction.md) “Env write ownership”). Do not re-enable on Nab.[^helpers]
- **`Helpers/functions.php`** — `defer()` → `app()`, `workshop_binary()` → move to Core; keep `php_binary()` / Defer types here.

# Related

- [Component dependency direction](../conventions/dependency-direction.md)
- [collections](collections.md) (satellite — support clique peer)
- [Component packages](../conventions/component-packages.md)

[^composer]: fabricate/nuts-and-bolts manifest
[^exception]: ScrapyardIOException
[^contracts]: Contract interfaces
[^helpers]: Helper files
[^defer]: Defer types
[^root]: Umbrella replace
[^dep]: Dependency direction rule
