---
type: Module
title: concurrency
description: fabricate/concurrency — ConcurrencyManager with sync/process/fork/fiber/pokio drivers; Core owns Concurrency MagicAlias + ConcurrencyServiceProvider; Workshop invoke-serialized-closure command.
resource: src/Fabricate/Concurrency/
tags: [component, concurrency, process]
generated: { by: cursor-agent, at: "2026-08-07T06:00:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: manager
    resource: src/Fabricate/Concurrency/ConcurrencyManager.php
    title: ConcurrencyManager
  - id: process-driver
    resource: src/Fabricate/Concurrency/ProcessDriver.php
    title: ProcessDriver
  - id: fiber-driver
    resource: src/Fabricate/Concurrency/FiberDriver.php
    title: FiberDriver
  - id: pokio-driver
    resource: src/Fabricate/Concurrency/PokioDriver.php
    title: PokioDriver
  - id: command
    resource: src/Fabricate/Concurrency/Console/InvokeSerializedClosureCommand.php
    title: invoke-serialized-closure
  - id: config
    resource: config/concurrency.php
    title: concurrency config
  - id: provider
    resource: src/Fabricate/Core/Providers/ConcurrencyServiceProvider.php
    title: Core ConcurrencyServiceProvider
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/concurrency` |
| Path | `src/Fabricate/Concurrency/` |
| Namespace | `Fabricate\Concurrency\` |
| Contracts | `Fabricate\Contracts\Concurrency\Driver` |
| Config | `config/concurrency.php` — `concurrency.default` |
| Dependencies | `laravel/serializable-closure`, `fabricate/process`, Workshop CLI |

# Drivers

| Driver | Behavior |
|--------|----------|
| `sync` | Runs closures inline — safe for tests / edge |
| `process` | Spawns `workshop invoke-serialized-closure` per task |
| `fork` | Requires suggested `spatie/fork`; CLI only |
| `fiber` | Cooperative PHP Fibers (interleaves on suspend); sketch-safe |
| `pokio` | Requires suggested `nunomaduro/pokio` (`async`/`await` over pcntl fork) |

Default: `process` (Laravel parity). Sketches AsyncNode defaults to `fiber` via `sketches.concurrency`.

# Workshop integration

`ProcessDriver` uses `WorkshopInstance::formatCommandString('invoke-serialized-closure')`. Fiber helpers: `Fabricate\Concurrency\Fiber\suspend` / `delay` / `await`.

# Related

- [process](process.md)
- [sketches](sketches.md)
- [console](console.md)
