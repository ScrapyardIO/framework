---
type: Module
title: bus
description: fabricate/bus — Dispatcher for sync/queued commands; Core owns Bus MagicAlias + BusServiceProvider (`bus`).
resource: src/Fabricate/Bus/
tags: [component, bus, draft]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T06:15:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: dispatcher
    resource: src/Fabricate/Bus/Dispatcher.php
    title: Dispatcher
  - id: composer
    resource: src/Fabricate/Bus/composer.json
    title: fabricate/bus package
  - id: provider
    resource: src/Fabricate/Core/Providers/BusServiceProvider.php
    title: Core BusServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Bus.php
    title: Bus MagicAlias
  - id: ownership
    resource: .okf/conventions/magic-aliases.md
    title: MagicAlias / provider ownership
  - id: root
    resource: composer.json
    title: Umbrella replace
  - id: queue
    resource: .okf/components/queue.md
    title: Queue (Phase 1)
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/bus`[^composer][^root] |
| Path | `src/Fabricate/Bus/` |
| PHP namespace | `Fabricate\Bus\` |
| Contracts | `Fabricate\Contracts\Bus\Dispatcher`, `QueueingDispatcher` |
| Package files | `composer.json`, `LICENSE.md`, `.gitattributes` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain Dispatcher/Queueable; Core binds `bus` + Magic Alias[^ownership] |

# How it works

1. `Bus::dispatch()` / `dispatchSync()` / `dispatchNow()` via `Fabricate\Bus\Dispatcher`.
2. Commands implementing `ShouldQueue` (or legacy `shouldQueue` property) go to the queue resolver; otherwise they run in-process.
3. Core `BusServiceProvider` (deferred) binds `bus` → `Dispatcher`, aliases contracts + `QueueingDispatcher`.
4. Magic Alias `Bus` → `bus`.
5. Batch repository wiring is deferred (Phase 1 stubs return null / map-dispatch).

Depends on [queue](queue.md) for queued dispatch.

Pest: `tests/Bus/BusTest.php`.

[^composer]: fabricate/bus package
[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
