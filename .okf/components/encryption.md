---
type: Module
title: encryption
description: fabricate/encryption — Encrypter encrypt/decrypt; Core owns Crypt MagicAlias + EncryptionServiceProvider (`encrypter`).
resource: src/Fabricate/Encryption/
tags: [component, encryption, draft]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T05:50:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: encrypter
    resource: src/Fabricate/Encryption/Encrypter.php
    title: Encrypter
  - id: cipher
    resource: src/Fabricate/Encryption/Cipher.php
    title: Cipher enum
  - id: composer
    resource: src/Fabricate/Encryption/composer.json
    title: fabricate/encryption package
  - id: provider
    resource: src/Fabricate/Core/Providers/EncryptionServiceProvider.php
    title: Core EncryptionServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Crypt.php
    title: Crypt MagicAlias
  - id: command
    resource: src/Fabricate/Core/Console/KeyGenerateCommand.php
    title: key:generate
  - id: machine
    resource: config/machine.php
    title: machine.key / cipher
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
| Composer | `fabricate/encryption`[^composer][^root] |
| Path | `src/Fabricate/Encryption/` |
| PHP namespace | `Fabricate\Encryption\` |
| Contracts | `Fabricate\Contracts\Encryption\*` |
| Package files | `composer.json`, `LICENSE.md`, `.gitattributes` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain Encrypter; Core binds `encrypter` + `Crypt` MagicAlias[^ownership] |

# How it works

1. `config/machine.php` — `cipher`, `key` (`APP_KEY`), `previous_keys` (`APP_PREVIOUS_KEYS`).
2. Core `EncryptionServiceProvider` (in `DefaultProviders`) binds `encrypter` from `machine.*` (parses `base64:` keys).
3. Magic Alias `Crypt` → `encrypter`; helpers `encrypt()` / `decrypt()`.
4. `workshop key:generate` uses `Encrypter::generateKey()` and writes `.env` via Filesystem.
5. Docs: Guide `encryption` + SDK `encryption` on the docs site.

`Cipher` is a string-backed enum (`AES_128_CBC`, `AES_256_CBC`, `AES_128_GCM`, `AES_256_GCM`) — no class-level cipher maps.

Pest: `tests/Encryption/EncrypterTest.php`.

[^composer]: fabricate/encryption package
[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
