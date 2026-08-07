---
type: Module
title: cache
description: fabricate/cache — CacheManager / file+redis stores; Core owns Cache MagicAlias + CacheServiceProvider; Workshop cache:clear / cache:forget.
resource: src/Fabricate/Cache/
tags: [component, cache]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T05:30:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: manager
    resource: src/Fabricate/Cache/CacheManager.php
    title: CacheManager
  - id: provider
    resource: src/Fabricate/Core/Providers/CacheServiceProvider.php
    title: Core CacheServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Cache.php
    title: Cache MagicAlias
  - id: config
    resource: config/cache.php
    title: cache config
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
| Composer | `fabricate/cache`[^root] |
| Path | `src/Fabricate/Cache/` |
| PHP namespace | `Fabricate\Cache\` |
| Contracts | `Fabricate\Contracts\Cache\*` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain — Core-free stores; Core binds `cache` / `cache.store` / RateLimiter + MagicAlias[^ownership] |

# How it works

## Public drivers (0.7)

ScrapyardIO targets **local desktop and edge / consumer** deployments — [AWS is not first-class](../conventions/aws-not-first-class.md).

| Driver | Role |
|--------|------|
| `file` | Default (`CACHE_STORE=file`) — disk under `storage/framework/cache/data` |
| `redis` | Shared/fast store via `fabricate/redis` |

`array` remains in config for in-process tests and the RateLimiter default (`CACHE_LIMITER_STORE`). Not a production store.

**Not supported:** database, memcached, dynamodb, S3/storage cloud disks, session, apc, octane.

## Bindings

Core `CacheServiceProvider` (deferred): `cache` → `CacheManager`, `cache.store` → default repository, `RateLimiter`.

Workshop: `cache:clear`, `cache:forget`.

## Related

- [redis](redis.md)
- [console](console.md) (schedule mutexes)
- [composer-replace](../conventions/composer-replace.md)

[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
