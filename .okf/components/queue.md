---
type: Module
title: queue
description: fabricate/queue — QueueManager / sync+redis Phase 1 drivers; Core owns Queue MagicAlias + QueueServiceProvider; Workshop queue:work / queue:restart.
resource: src/Fabricate/Queue/
tags: [component, queue, draft]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T06:15:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: manager
    resource: src/Fabricate/Queue/QueueManager.php
    title: QueueManager
  - id: composer
    resource: src/Fabricate/Queue/composer.json
    title: fabricate/queue package
  - id: provider
    resource: src/Fabricate/Core/Providers/QueueServiceProvider.php
    title: Core QueueServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Queue.php
    title: Queue MagicAlias
  - id: config
    resource: config/queue.php
    title: queue config
  - id: ownership
    resource: .okf/conventions/magic-aliases.md
    title: MagicAlias / provider ownership
  - id: root
    resource: composer.json
    title: Umbrella replace
  - id: bus
    resource: .okf/components/bus.md
    title: Bus
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/queue`[^composer][^root] |
| Path | `src/Fabricate/Queue/` |
| PHP namespace | `Fabricate\Queue\` |
| Contracts | `Fabricate\Contracts\Queue\*` |
| Package files | `composer.json`, `LICENSE.md`, `.gitattributes` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain queues/connectors/worker; Core binds `queue` / `queue.connection` + MagicAlias[^ownership] |

# How it works

## Public drivers (Phase 1)

ScrapyardIO targets **local desktop and edge / consumer** deployments.

| Driver | Role |
|--------|------|
| `sync` | Default (`QUEUE_CONNECTION=sync`) — run jobs in-process |
| `redis` | Shared queue via `fabricate/redis` |
| `database` | SQL-backed queue via `fabricate/database` |
| `deferred` | Defer until after the current request/process |
| `failover` | Try configured connections in order (default: redis → deferred) |
| `null` | Discard jobs |
| `background` | Connector registered; use when wiring background push |

**Not registered as public:** Beanstalkd. **AWS is not first-class** — no SQS / DynamoDB failed storage (see [aws-not-first-class](../conventions/aws-not-first-class.md)). Database queue is registered.

## Failed jobs

Phase 1: `null` (default) and `file`.

## Bindings

Core `QueueServiceProvider` (deferred): `queue` → `QueueManager`, `queue.connection`, `queue.worker`, `queue.listener`, `queue.routes`, `queue.failer`.

Worker `resetScope` clears log context when bound, resets DB connections only when `bound('db')`, forgets scoped instances, and calls `Fabricate\MagicAliases\MagicAlias::clearResolvedInstances()`.

Workshop: `queue:work`, `queue:restart` (registered in `WorkshopServiceProvider`).

Config: `config/queue.php`.

Pest: `tests/Queue/QueueTest.php` (no hanging worker).

## Deferred

- Database/SQS/Beanstalkd connector registration
- Batch persistence
- Horizon
- `Core\Bus\Dispatchable` / `PendingDispatch` (Job::dispatch() helpers)
- Database-backed queue connector and failed-job storage (model serialization now targets Polisher)

[^composer]: fabricate/queue package
[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
