---
type: Convention
title: Component dependency direction
description: Core may know everything; nothing below Core depends on Core; NutsAndBolts may use only its moons (support satellites); other components never depend on Core.
tags: [convention, architecture, components, core, nuts-and-bolts]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T21:27:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-06T21:27:00Z" }
status: stable
sources:
  - id: agents
    resource: AGENTS.md
    title: Package agent rules
  - id: nab
    resource: src/Fabricate/NutsAndBolts/
    title: NutsAndBolts component tree
  - id: env
    resource: src/Fabricate/NutsAndBolts/Env.php
    title: Env (read-side support; writers belong elsewhere)
---

# Rule

Only **Core** looks across the whole graph. Everything else stays **Core-free**.

**NutsAndBolts may depend on its moons** (Collections, Conditionable, Macroable, Reflection, …) **and `fabricate/contracts`** (interfaces only, e.g. `ServiceContainer`). It must **not** depend on Filesystem, Broadcasting, Config, Chassis, domain modules, or Core.

```
                    ┌─────────┐
                    │  Core   │  may be aware of everything
                    └────┬────┘
                         │ knows
                         ▼
        ┌────────────────────────────────┐
        │ Other components               │  prefer NutsAndBolts;
        │ (Filesystem, Broadcasting,     │  optional non-Core peers
        │  Config, Chassis, domains, …) │  (e.g. Broadcasting↔Filesystem);
        └────────────┬───────────────────┘  must NOT know Core
                     │
                     ▼
        ┌────────────────────────────────┐
        │ Support clique                 │  Nab ↔ moons;
        │  NutsAndBolts + moons          │  Nab → contracts OK;
        │  + fabricate/contracts         │  must NOT know Core /
        │                                │  Chassis / other comps
        └────────────────────────────────┘
```

# Definitions

| Term | Meaning |
|------|---------|
| **Core** | Application runtime spine (Machine, bootstrap, `app()` / path / config helpers, Workshop wiring, …). May import any Fabricate component. |
| **NutsAndBolts** | Primary support component. May depend on **moons + `fabricate/contracts`**. Must not know Core, Chassis, or other components (including Filesystem). |
| **Moon / satellite** | Support sibling that orbits NutsAndBolts (Collections, Conditionable, Macroable, Reflection). May depend on Nab, other moons, and contracts. Must not know Core. |
| **Other components** | Filesystem, Broadcasting, Config, Chassis, domain modules, …. Prefer Nab; peer deps among themselves OK when the domain justifies it (e.g. Broadcasting using Filesystem for `.env` install writes). **Never** depend on Core. |

# Hard bans

1. **Anyone except Core ↛ Core**
2. **NutsAndBolts ↛ non-moon / non-contracts components** — no Filesystem, Broadcasting, Config, Chassis, …
3. **Other components ↛ Core** — Core reaches down; they do not reach up

# Soft / allowed

- Core → anything
- Nab ↔ moons; Nab → `fabricate/contracts` (interfaces only)
- Other component → Nab; optionally other non-Core peers
- **Broadcasting ↔ Filesystem** — fine. Install/scaffold flows that mutate `.env` belong here (or Core console commands), not on Nab `Env`

# Env write ownership

Laravel hung `Env::writeVariable(s)` on Support and reached for Filesystem for starter-kit / broadcasting install DX — not a layering ideal.

ScrapyardIO:

- Nab `Env` = **read / repository** side (`get`, adapters, `env()` helper)
- **`.env` file mutation** = Broadcasting (websockets / broadcast install) and/or Core Workshop commands, using **Filesystem**
- Prefer a Broadcasting (or Core) helper that **uses** Nab `Env` + Filesystem over making Nab `Env` inherit Filesystem knowledge. Subclassing static `Env` is awkward; composition wins (`Broadcasting\EnvFile` / install command calling into line-edit logic)

# Known violations (fix)

1. Nab `Env.php` — `writeVariable(s)` + Filesystem helpers are **commented out** in-source with a relocation flag pointing here; restore into Broadcasting/Core + Filesystem when those land (do not re-enable on Nab).[^env]
2. Nab `Helpers/functions.php`: `defer()` → `app()`, `workshop_binary()` — move to Core.[^nab]

# 0.6 donor note (Broadcasting / websockets)

0.6 kitchen-sink has **hooks, not a component**: commented `BroadcastServiceProvider` in `DefaultProviders`, commented MagicAlias, Events `Dispatcher` scaffolding comments for `ShouldBroadcast`. No `src/Fabricate/Broadcasting/` tree yet — restore/build when websockets are in scope.

# Related

- [NutsAndBolts namespace packaging](../orientation/nuts-and-bolts-composition.md)
- [Component packages](component-packages.md)
- [nuts-and-bolts](../components/nuts-and-bolts.md)
- [chassis](../components/chassis.md) — other component (Core-free container)
- [contracts](../components/contracts.md) — public swap surfaces (`ServiceContainer`, …)

[^env]: Env (read-side support; writers belong elsewhere)
[^nab]: NutsAndBolts component tree
