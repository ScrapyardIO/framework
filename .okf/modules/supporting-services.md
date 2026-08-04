---
type: Catalog
title: Supporting services
description: Non-hardware Fabricate modules — cache, queue, Redis, filesystem, log, events, bus, process, collections, console helpers.
tags: [modules, services]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Application services list
  - id: src
    resource: src/Fabricate
    title: Fabricate service directories
---

# Catalog

| Domain | Namespace area | Notes |
|--------|----------------|-------|
| Cache | `Fabricate\Cache` | + `config/cache.php` |
| Queue | `Fabricate\Queue` | + `config/queue.php` |
| Redis | `Fabricate\Redis` | + `config/redis.php` |
| Filesystem | `Fabricate\Filesystem` | + `config/filesystems.php` |
| Log | `Fabricate\Log` | + `config/logging.php` |
| Events | `Fabricate\Events` | + AssemblyLine `withEvents` |
| Bus | `Fabricate\Bus` / Core Bus | Command bus |
| Process | `Fabricate\Process` | Process execution + fakes |
| Pipeline | `Fabricate\Pipeline` | Pipelines |
| Console | `Fabricate\Console` | Commands, prompts, scheduling hooks |
| Collections / Macroable / Conditionable / Reflection | under NutsAndBolts PSR-4 merge | Support primitives |
| NutsAndBolts | helpers, MagicAliases, Defer, geometry, … | Shared utilities |
| Database | `Fabricate\Database` | Present; use only when app needs it |
| Encryption / Testing | respective namespaces | Support |

Deep dives for these can be added later as separate concepts; this catalog is the routing map.[^readme]

[^readme]: Application services list
[^src]: Fabricate service directories
