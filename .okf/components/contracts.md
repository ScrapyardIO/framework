---
type: Module
title: contracts
description: fabricate/contracts — curated public swap surfaces (PSR-shaped interfaces) for Fabricate components; no concretes.
resource: src/Fabricate/Contracts/
tags: [component, contracts, interfaces]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T23:32:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: composer
    resource: src/Fabricate/Contracts/composer.json
    title: fabricate/contracts manifest
  - id: service-container
    resource: src/Fabricate/Contracts/Chassis/ServiceContainer.php
    title: ServiceContainer
  - id: self-building
    resource: src/Fabricate/Contracts/Chassis/SelfBuilding.php
    title: SelfBuilding
  - id: contextual
    resource: src/Fabricate/Contracts/Chassis/ContextualAttribute.php
    title: ContextualAttribute
  - id: chassis-exception
    resource: src/Fabricate/Contracts/Chassis/ChassisException.php
    title: ChassisException
  - id: scrapyard-exception
    resource: src/Fabricate/Contracts/Core/ScrapyardIOException.php
    title: ScrapyardIOException
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
| Composer | `fabricate/contracts`[^composer] |
| Path | `src/Fabricate/Contracts/` |
| PHP namespace | `Fabricate\Contracts\` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Swap-surface package — interfaces (and thin exception bases); **no** Chassis/Core concrete imports[^dep] |

# How it works

## Mental model

Curated **public contracts** so consumers and sibling packages can type-hint without taking a concrete component. This is **not** Laravel’s mega-`illuminate/contracts` dump — owners stay obvious (`Contracts\Chassis\…`, `Contracts\Core\…`), and the set grows with restored domains.

**Rule:** swap surfaces live here. Chassis-local wireframe markers stay under `Fabricate\Chassis\Contracts\` (e.g. `WireframeServiceContainer`).[^service-container]

```
fabricate/contracts                 fabricate/chassis
┌────────────────────────────┐      ┌──────────────────────────────┐
│ ServiceContainer           │◄─────│ WireframeServiceContainer    │
│ PSR-11 + ArrayAccess +     │      │ + bind/singleton/when/tag…   │
│ make/call/instance/        │      │ Chassis (concrete)           │
│ resolved/bound/            │      └──────────────────────────────┘
│ afterResolving             │
│ register/resolveProvider/  │
│ hasDebugModeEnabled        │
│ SelfBuilding, …            │
└────────────────────────────┘
         ▲
         │ typed by MagicAlias / Manager (consumers)
```

## On disk (growing)

| Area | Symbols |
|------|---------|
| `Chassis\` | `ServiceContainer` (consumer surface), `SelfBuilding`, `ContextualAttribute`, `ContextualBindingBuilder`, `ChassisException`[^service-container][^self-building][^contextual][^chassis-exception] |
| `Config\` | `Repository` |
| `Console\` | `CLIKernel`, `CLIMachine`, `ConsoleProgram`, `Isolatable`, `PromptsForMissingInput` |
| `Core\` | `Program` (extends Chassis wireframe surface), `CachesConfiguration`, `ScrapyardIOException`[^scrapyard-exception] |
| `Events\` | `Dispatcher`, `ShouldBeDiscovered`, `ShouldDispatchAfterCommit`, `ShouldHandleEventsAfterCommit` |
| `Debug\` | `ExceptionHandler` |
| `Bus\` | `Dispatcher`, `QueueingDispatcher` |
| `Queue\` | `Factory`, `QueueFactory`, `Queue`, `Job`, `ShouldQueue`, uniqueness/encryption markers, … |
| `Filesystem\` | `Filesystem`, `FilesystemFactory`, `Cloud`, `FileNotFoundException`, `LockTimeoutException` |
| `Log\` | `ContextLogProcessor` (Monolog processor marker; Context repository still deferred) |

Requires `psr/container`. Suggests `psr/simple-cache`.[^composer]

## Chassis relationship

- Public consumer contract is **`ServiceContainer`** — PSR-11 + `ArrayAccess` + `make` / `call` / `instance` / `resolved` / `afterResolving` + `resolveEnvironmentUsing` / `cliMachine` (consumer + env-resolution hooks).
- Full binding API stays on Chassis-local `WireframeServiceContainer`.
- Concrete container: [chassis](chassis.md) (`Chassis` implements `WireframeServiceContainer`).
- Domains / MagicAlias base type-hint `ServiceContainer`, not Chassis/Program (see [magic-aliases](../conventions/magic-aliases.md)).

## Console / Core relationship

- `CLIKernel` — Workshop kernel contract; Core binds `ConsoleKernel` via `AssemblyLine::withKernels()` (see [core](core.md)).
- `CLIMachine` — Symfony Application surface (`WorkshopInstance`).
- `Program` — Machine contract (paths, providers, boot, …); fat vs container consumer split stays intentional.

# Surface

| Symbol | Role |
|--------|------|
| `Chassis\ServiceContainer` | Public consumer container (get/has/make/call/instance/resolved/afterResolving/resolveEnvironmentUsing/cliMachine + ArrayAccess) |
| `Chassis\SelfBuilding` | Marker for self-constructing types |
| `Chassis\ContextualAttribute` | Marker for contextual DI attributes |
| `Chassis\ContextualBindingBuilder` | Fluent contextual binding contract |
| `Chassis\ChassisException` | Abstract Chassis exception base |
| `Config\Repository` | Config bag swap surface |
| `Console\CLIKernel` | CLI kernel contract |
| `Console\CLIMachine` | Workshop / Symfony app contract |
| `Core\Program` | Application contract |
| `Core\CachesConfiguration` | Config/services cache paths |
| `Core\ScrapyardIOException` | Framework exception base (+ `invalidProperty`) |
| `Debug\ExceptionHandler` | Exception handler contract |
| `Events\Dispatcher` | Sync event bus swap surface |
| `Events\ShouldBeDiscovered` | Listener discovery opt-out marker |
| `Events\ShouldDispatchAfterCommit` | Marker for post-commit dispatch (future) |
| `Events\ShouldHandleEventsAfterCommit` | Marker for post-commit listeners (future) |

# Related

- [chassis](chassis.md) — concrete container + wireframe marker
- [core](core.md) — Machine / ConsoleKernel / Workshop
- [events](events.md) — domain Dispatcher implementation
- [Component dependency direction](../conventions/dependency-direction.md)
- [MagicAlias and provider ownership](../conventions/magic-aliases.md)
- [Component packages](../conventions/component-packages.md)

[^composer]: fabricate/contracts manifest
[^service-container]: ServiceContainer
[^self-building]: SelfBuilding
[^contextual]: ContextualAttribute
[^chassis-exception]: ChassisException
[^scrapyard-exception]: ScrapyardIOException
[^root]: Umbrella replace
[^dep]: Dependency direction rule
