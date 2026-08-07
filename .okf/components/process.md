---
type: Module
title: process
description: fabricate/process — Symfony Process wrapper; Core owns Process MagicAlias + ProcessServiceProvider (`process`).
resource: src/Fabricate/Process/
tags: [component, process, draft]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T06:05:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: factory
    resource: src/Fabricate/Process/Factory.php
    title: Factory
  - id: pending
    resource: src/Fabricate/Process/PendingProcess.php
    title: PendingProcess
  - id: composer
    resource: src/Fabricate/Process/composer.json
    title: fabricate/process package
  - id: provider
    resource: src/Fabricate/Core/Providers/ProcessServiceProvider.php
    title: Core ProcessServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Process.php
    title: Process MagicAlias
  - id: ownership
    resource: .okf/conventions/magic-aliases.md
    title: MagicAlias / provider ownership
  - id: root
    resource: composer.json
    title: Umbrella replace
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/process`[^composer][^root] |
| Path | `src/Fabricate/Process/` |
| PHP namespace | `Fabricate\Process\` |
| Contracts | `Fabricate\Contracts\Process\*` |
| Package files | `composer.json`, `LICENSE.md`, `.gitattributes` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain Factory/PendingProcess; Core binds `process` + Magic Alias[^ownership] |

# How it works

1. `Process::run()` / `start()` via Factory → PendingProcess → Symfony Process.
2. Pools, pipes, concurrent runs; fakes for Pest (`fake`, `assertRan`, …).
3. Core `ProcessServiceProvider` (deferred) binds `process` + `Factory::class`.
4. Magic Alias `Process` → `process`.

Pest: `tests/Process/ProcessAndConcurrencyTest.php`.

[^composer]: fabricate/process package
[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
