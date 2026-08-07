---
type: Module
title: redis
description: fabricate/redis — RedisManager + phpredis/predis connectors; Core owns Redis MagicAlias + RedisServiceProvider.
resource: src/Fabricate/Redis/
tags: [component, redis]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T05:30:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: draft
sources:
  - id: manager
    resource: src/Fabricate/Redis/RedisManager.php
    title: RedisManager
  - id: provider
    resource: src/Fabricate/Core/Providers/RedisServiceProvider.php
    title: Core RedisServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Redis.php
    title: Redis MagicAlias
  - id: config
    resource: config/redis.php
    title: redis config
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
| Composer | `fabricate/redis`[^root] |
| Path | `src/Fabricate/Redis/` |
| PHP namespace | `Fabricate\Redis\` |
| Contracts | `Fabricate\Contracts\Redis\*` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain — Core-free; Core binds `redis` / `redis.connection`[^ownership] |

# How it works

Standalone Redis component (not nested under Cache). Cache’s `redis` store consumes `$app['redis']`.

| Client | Config |
|--------|--------|
| `phpredis` | Default when `ext-redis` present (`REDIS_CLIENT=phpredis`) |
| `predis` | Optional Composer package (`REDIS_CLIENT=predis`) |

Neither client is a hard require — both are `suggest` on the umbrella (`predis/predis` is also `require-dev` for CI). Typical edge/SBC use: local Redis or none (file cache).

**Scan parity:** `PhpRedisConnection` and `PredisConnection` both expose `scan` / `zscan` / `hscan` / `sscan`. Predis wrappers coerce a `null` cursor to `0` (Redis 7.4+ rejects null with `ERR invalid cursor`) and return `[cursor, results]` so Illuminate-style loops work for both clients.

## Related

- [cache](cache.md)

[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
