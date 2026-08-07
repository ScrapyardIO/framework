---
type: Convention
title: MagicAlias and provider ownership
description: Domain components stay free of MagicAlias and Chassis; Core owns concrete MagicAliases and service providers that wire those domains.
tags: [convention, magic-alias, service-provider, core, components, composer]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T21:44:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-06T21:44:00Z" }
status: stable
sources:
  - id: dep
    resource: .okf/conventions/dependency-direction.md
    title: Component dependency direction
---

# Preferred rule (true domain independence)

Domain components (Filesystem, Config, Cache, GPIO, …) ship **domain logic only** — managers, drivers, contracts, config shapes. They do **not** ship:

- concrete `MagicAlias` classes
- `*ServiceProvider` classes
- composer `require` / `suggest` on Chassis, Core, or `fabricate/magic-aliases`

**Core** owns the glue (allowed: Core may know everything):

| Piece | Home |
|-------|------|
| `MagicAlias` base (+ AliasLoader) | Own small component **or** under Core — may type Chassis / container contract |
| Concrete aliases (`Storage`, `Config`, `GPIO`, …) | **Core** (e.g. `Fabricate\Core\MagicAliases\…` or `Fabricate\NutsAndBolts\MagicAliases` only as legacy donor layout) |
| Domain `*ServiceProvider`s | **Core** (e.g. `Fabricate\Core\Providers\…` or aggregated default providers) |
| Default provider list | **`Fabricate\Core\DefaultProviders`** (`DefaultProviders::make()`) — not Nab `ServiceProvider` |
| Domain package | Managers / implementations only; Nab moons as needed |

```
Domain packages  →  Nab moons (and peer domains as needed)
Core             →  domains + concrete MagicAliases + providers + Chassis + everything
MagicAlias base  →  Fabricate\Contracts\Chassis\ServiceContainer (not Program / not Wireframe)
```

`ServiceContainer` carries the consumer methods MagicAlias / managers / ServiceProviders need (`make`/`call`/`instance`/`resolved`/`bound`/`afterResolving`/`register`/`resolveProvider`/`hasDebugModeEnabled` + ArrayAccess). Full bind API stays on Chassis `WireframeServiceContainer`.

# Why

- Domains stay installable/testable without Chassis, MagicAliases, or a booted Machine.
- No PHP `extends MagicAlias` / `extends ServiceProvider` trap inside domain trees.
- Matches [dependency direction](dependency-direction.md): Core reaches down; domains never reach up.
- Optional surfaces stay optional — apps that don’t boot Core simply don’t get aliases/providers.

# Tradeoff

Adding a new domain means **Core wiring** (provider + alias + default provider list / discovery map) updates in Core, not inside the domain package. That is acceptable: Core is the composition root.

If a third-party/extension package must self-register later, use package discovery that still points at provider classes **owned by that extension** (or a dedicated bridge package) — not by forcing every first-party domain to ship providers.

# Rejected for first-party domains

- Domain `require` Chassis or Core for aliases/providers
- Domain always-autoloaded `extends MagicAlias` with only Composer `suggest`
- Parking all concrete aliases under Nab

# Related

- [Component dependency direction](dependency-direction.md)
