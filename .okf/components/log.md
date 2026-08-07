---
type: Module
title: log
description: fabricate/log — LogManager / Logger over Monolog; Core owns Log MagicAlias + LogServiceProvider; Context repository deferred (Queue/Eloquent).
resource: src/Fabricate/Log/
tags: [component, log, monolog]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T03:50:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: composer
    resource: src/Fabricate/Log/composer.json
    title: fabricate/log manifest
  - id: manager
    resource: src/Fabricate/Log/LogManager.php
    title: LogManager
  - id: logger
    resource: src/Fabricate/Log/Logger.php
    title: Logger wrapper
  - id: contract
    resource: src/Fabricate/Contracts/Log/ContextLogProcessor.php
    title: ContextLogProcessor contract
  - id: provider
    resource: src/Fabricate/Core/Providers/LogServiceProvider.php
    title: Core LogServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Log.php
    title: Log MagicAlias
  - id: config
    resource: config/logging.php
    title: logging config
  - id: root
    resource: composer.json
    title: Umbrella replace
  - id: ownership
    resource: .okf/conventions/magic-aliases.md
    title: MagicAlias / provider ownership
  - id: dep
    resource: .okf/conventions/dependency-direction.md
    title: Dependency direction
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/log`[^composer] |
| Path | `src/Fabricate/Log/` |
| PHP namespace | `Fabricate\Log\` |
| Contracts | `Fabricate\Contracts\Log\ContextLogProcessor`[^contract] |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain — Core-free; LogManager types `Program` (needs `storagePath` / env); Core binds `log` + alias + provider[^ownership][^dep] |

Ported from 0.6 donor. Misplaced concretes under `Contracts\Log` were removed — only the processor contract remains there.

# How it works

## Mental model

1. **Domain** `LogManager` + `Logger` — channel drivers (single/daily/stack/stderr/syslog/errorlog/monolog/null) via Monolog; optional `MessageLogged` through Events.
2. **Core glue** — early Machine singleton `'log'` (HandleExceptions runs before RegisterProviders); `LogServiceProvider` uses `singletonIf` + aliases; MagicAlias `Log` accessor `log`.
3. **Config** — `config/logging.php` (framework + app); default stack → single file under `storage/logs/`.

```php
app('log')->info('boot complete');
Log::channel('single')->debug('trace', ['k' => 1]);
logger('hello');
```

## Out of scope (this restore)

| Piece | Why |
|-------|-----|
| `Context\Repository` + `ContextServiceProvider` | Needs Queue `SerializesModels` + Eloquent `ModelNotFoundException` |
| Context MagicAlias / hydrate hooks | Same |

`Context\*` files remain on disk for a later Queue/DB restore; do not register ContextServiceProvider yet.

## API (domain)

| Symbol | Role |
|--------|------|
| `LogManager` | Channel factory + PSR-3[^manager] |
| `Logger` | Monolog wrapper; dispatches `MessageLogged`[^logger] |
| `Events\MessageLogged` | Fired when writing (if events bound) |
| `Concerns\ParsesLogConfiguration` | Level / handler helpers |
| `Contracts\Log\ContextLogProcessor` | Monolog processor marker[^contract] |

## Core surface

| Symbol | Role |
|--------|------|
| `MagicAliases\Log` | Accessor `log`[^alias] |
| `Providers\LogServiceProvider` | `singletonIf('log')` + aliases[^provider] |
| `Machine` early bind | `singleton('log', …)` before providers |
| `DefaultProviders` | Includes `LogServiceProvider` |
| Helpers | `logger()` / `logs()` in Core helpers |

# Related

- [contracts](contracts.md) — `Contracts\Log\ContextLogProcessor`
- [core](core.md) — Machine / HandleExceptions / DefaultProviders
- [events](events.md) — optional MessageLogged dispatch
- [MagicAlias and provider ownership](../conventions/magic-aliases.md)
- [Composer replace](../conventions/composer-replace.md)

[^composer]: fabricate/log manifest
[^manager]: LogManager
[^logger]: Logger wrapper
[^contract]: ContextLogProcessor contract
[^provider]: Core LogServiceProvider
[^alias]: Log MagicAlias
[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
[^dep]: Dependency direction
