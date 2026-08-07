---
type: Module
title: validation
description: fabricate/validation — Validator factory + rules; Core owns Validator MagicAlias + ValidationServiceProvider (`validator`).
resource: src/Fabricate/Validation/
tags: [component, validation, draft]
generated: { by: cursor-agent, at: "2026-08-07T06:00:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: factory
    resource: src/Fabricate/Validation/Factory.php
    title: Factory
  - id: provider
    resource: src/Fabricate/Core/Providers/ValidationServiceProvider.php
    title: Core ValidationServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Validator.php
    title: Validator MagicAlias
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/validation` |
| Path | `src/Fabricate/Validation/` |
| PHP namespace | `Fabricate\Validation\` |
| Contracts | `Fabricate\Contracts\Validation\*` |
| Requires | `fabricate/translation`, `brick/math`, `egulias/email-validator`, `symfony/http-foundation` |
| Umbrella | `replace` → `self.version` |

# How it works

1. Core `ValidationServiceProvider` binds `validator` (`Factory`) using `translator` + container.
2. Magic Alias `Validator` → `validator`.
3. Workshop prompts (`ConfiguresPrompts`) expect `validator` — now satisfied by default providers.
4. **Deferred:** `validation.presence` (DB unique/exists) — interface + stub `DatabasePresenceVerifier`; Core does **not** register it until `fabricate/database`.
5. **Deferred:** `UncompromisedVerifier` / `NotPwnedVerifier` — class ported; optional HTTP factory; passes when unbound (offline-safe).

Pest: `tests/Validation/ValidatorTest.php`.
