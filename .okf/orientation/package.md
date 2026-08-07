---
type: Orientation
title: Package (0.7)
description: scrapyard-io/framework 0.7.x — Fabricate umbrella reconstituting components and config.
resource: .
tags: [orientation, framework, fabricate, 0.7]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T21:08:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: composer
    resource: composer.json
    title: Package name, version, require, replace, autoload
  - id: readme
    resource: README.md
    title: Package README (vision marketing)
  - id: gitattributes
    resource: .gitattributes
    title: export-ignore for .okf, AGENTS.md, tests
  - id: tree
    resource: src/Fabricate/
    title: On-disk Fabricate tree
---

# What it is

Composer package `scrapyard-io/framework` at **0.7.0** — the Fabricate application framework umbrella.[^composer]

This line is **reconstituting**. On disk: support components (NutsAndBolts + moons), Chassis, Config, Contracts, MagicAlias base, Core (Machine + ConsoleKernel), and Console (`WorkshopInstance`). Sibling app skeleton at `…/ScrapyardIO/scrapyard-io` path-symlinks this framework at `^0.7.0`.[^tree]

| Field | Value |
|-------|-------|
| Name | `scrapyard-io/framework` |
| Version | `0.7.0` |
| PHP | `^8.4\|^8.5\|^8.6` |
| CLI milestone | `workshop` lists Symfony defaults as **ScrapyardIO Framework 0.7.0** |
| Namespace root | `Fabricate\` → `src/Fabricate/` |

`.okf/` and `AGENTS.md` are `export-ignore`.[^gitattributes]

# What belongs elsewhere

Chip drivers, GPIO transport, and native bindings stay in companion packages (`gpio-framework`, `dept-of-scrapyard-robotics/*`, `microscrap/*`, waveforms, tubes). Framework runtime pieces (Machine, Chassis, Config, domain registries, …) belong **here** as they are restored.

# Vision vs code

Root `README.md` markets the full embedded/GUI/IC story.[^readme] OKF should track code + restore intent, not freeze hollowness.

# Related

| Topic | Concept |
|-------|---------|
| Shared NutsAndBolts namespace packaging | [NutsAndBolts namespace packaging](nuts-and-bolts-composition.md) |
| Components | [components/](../components/index.md) |
| Core / Workshop CLI | [core](../components/core.md) |
| Consume | [Require the framework](../playbooks/require-framework.md) |

[^composer]: Package name, version, require, replace, autoload
[^readme]: Package README (vision marketing)
[^gitattributes]: export-ignore for .okf, AGENTS.md, tests
[^tree]: On-disk Fabricate tree
