---
type: Module
title: config
description: fabricate/config — nested-key configuration Repository (ArrayAccess + typed getters).
resource: src/Fabricate/Config/
tags: [component, config]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T02:22:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T02:22:00Z" }
status: stable
sources:
  - id: composer
    resource: src/Fabricate/Config/composer.json
    title: fabricate/config manifest
  - id: repository
    resource: src/Fabricate/Config/Repository.php
    title: Repository class
  - id: contract
    resource: src/Fabricate/Contracts/Config/Repository.php
    title: Config Repository contract
  - id: root
    resource: composer.json
    title: Umbrella replace
  - id: chassis
    resource: src/Fabricate/Chassis/Chassis.php
    title: Chassis @property-read config
  - id: helpers
    resource: src/Fabricate/Core/Helpers/helpers.php
    title: config() helper (Core)
  - id: trap
    resource: .okf/traps/env-outside-config.md
    title: env() vs config() rule
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/config`[^composer] |
| Path | `src/Fabricate/Config/` |
| PHP namespace | `Fabricate\Config\` |
| Contract | `Fabricate\Contracts\Config\Repository`[^contract] |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Other component — Core-free; Core binds `config` at bootstrap |

Angel reports this component **completely ported** (parity with 0.6: Repository-only package).

# How it works

## Mental model

In-memory nested config bag. Dot-keys via Nab `Arr`; Macroable; `ArrayAccess` for `$config['app.name']`.[^repository]

```php
$config = new \Fabricate\Config\Repository(['app' => ['name' => 'ScrapyardIO']]);
$config->get('app.name');           // ScrapyardIO
$config->string('app.name');        // typed getter (throws if wrong type)
$config->set('app.debug', true);
```

## API

| Method | Role |
|--------|------|
| `has` / `get` / `getMany` / `all` | Read |
| `string` / `integer` / `float` / `boolean` / `array` / `collection` | Typed getters (throw `InvalidArgumentException` on type mismatch) |
| `set` / `prepend` / `push` | Write |
| ArrayAccess | `offset*` → has/get/set |

Contract surface is the smaller set (`has`/`get`/`all`/`set`/`prepend`/`push`); typed helpers are concrete-only.[^contract][^repository]

## Runtime wiring (Core — not this package)

| Piece | Home |
|-------|------|
| Container binding `config` | Core bootstrap (`LoadConfiguration` when restored) → `instance('config', new Repository($items))` |
| `$machine->config` | Chassis `__get` → `make('config')`[^chassis] |
| `config()` helper | Core helpers — uses `app('config')`[^helpers] |
| `env()` in config files only | [env-outside-config](../traps/env-outside-config.md)[^trap] |

This package does **not** load PHP config files from disk — that stays Core.

# Requires

```json
"fabricate/collections": "^0.7.0",
"fabricate/contracts": "^0.7.0"
```

Uses Nab `Arr` / `Collection` / `Macroable` via Collections + umbrella Nab namespace packaging.[^composer]

# Surface

| Symbol | Role |
|--------|------|
| `Repository` | Concrete config store |
| `Contracts\Config\Repository` | Public swap surface |

# Related

- [contracts](contracts.md) — `Config\Repository`
- [chassis](chassis.md) — `@property-read` `$config`
- [core](core.md) — binds / loads config at bootstrap
- [collections](collections.md) — Arr / Collection
- [env() outside config](../traps/env-outside-config.md)

[^composer]: fabricate/config manifest
[^repository]: Repository class
[^contract]: Config Repository contract
[^root]: Umbrella replace
[^chassis]: Chassis @property-read config
[^helpers]: config() helper (Core)
[^trap]: env() vs config() rule
