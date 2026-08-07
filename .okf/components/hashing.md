---
type: Module
title: hashing
description: fabricate/hashing — HashManager + bcrypt/argon drivers; Core owns Hash MagicAlias + HashServiceProvider (`hash`).
resource: src/Fabricate/Hashing/
tags: [component, hashing, draft]
generated: { by: cursor-agent, at: "2026-08-07T06:00:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: manager
    resource: src/Fabricate/Hashing/HashManager.php
    title: HashManager
  - id: bcrypt
    resource: src/Fabricate/Hashing/BcryptHasher.php
    title: BcryptHasher
  - id: argon
    resource: src/Fabricate/Hashing/ArgonHasher.php
    title: ArgonHasher
  - id: argon2id
    resource: src/Fabricate/Hashing/Argon2IdHasher.php
    title: Argon2IdHasher
  - id: composer
    resource: src/Fabricate/Hashing/composer.json
    title: fabricate/hashing package
  - id: provider
    resource: src/Fabricate/Core/Providers/HashServiceProvider.php
    title: Core HashServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Hash.php
    title: Hash MagicAlias
  - id: config
    resource: config/hashing.php
    title: hashing.driver / bcrypt / argon
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
| Composer | `fabricate/hashing`[^composer][^root] |
| Path | `src/Fabricate/Hashing/` |
| PHP namespace | `Fabricate\Hashing\` |
| Contracts | `Fabricate\Contracts\Hashing\Hasher` |
| Package files | `composer.json`, `LICENSE.md`, `.gitattributes` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain hashers + manager; Core binds `hash` + `Hash` MagicAlias[^ownership] |

# How it works

1. `config/hashing.php` — `driver` (`HASH_DRIVER`), bcrypt rounds (`BCRYPT_ROUNDS`), argon memory/time/threads, `HASH_VERIFY` for algorithm checks.
2. Core `HashServiceProvider` (in `DefaultProviders`) binds `hash` as `HashManager` (deferrable).
3. Magic Alias `Hash` → `hash`; helper `bcrypt()` forces the bcrypt driver.
4. Drivers: `bcrypt`, `argon` (Argon2i), `argon2id` (Argon2id) — all via PHP `password_*` APIs.
5. Docs: Guide `hashing` + SDK `hashing` on the docs site.

Pest: `tests/Hashing/HashTest.php`.

[^composer]: fabricate/hashing package
[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
