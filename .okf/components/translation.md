---
type: Module
title: translation
description: fabricate/translation — Translator + loaders; Core owns Lang MagicAlias + TranslationServiceProvider (`translator`).
resource: src/Fabricate/Translation/
tags: [component, translation, draft]
generated: { by: cursor-agent, at: "2026-08-07T06:00:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: translator
    resource: src/Fabricate/Translation/Translator.php
    title: Translator
  - id: provider
    resource: src/Fabricate/Core/Providers/TranslationServiceProvider.php
    title: Core TranslationServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Lang.php
    title: Lang MagicAlias
  - id: lang
    resource: src/Fabricate/Translation/lang/en/validation.php
    title: English validation messages
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/translation` |
| Path | `src/Fabricate/Translation/` |
| PHP namespace | `Fabricate\Translation\` |
| Contracts | `Fabricate\Contracts\Translation\*` |
| Umbrella | `replace` → `self.version` |

# How it works

1. Core `TranslationServiceProvider` binds `translation.loader` (framework `lang/` + app `path.lang`) and `translator`.
2. `config/machine.php` — `locale`, `fallback_locale` (`APP_LOCALE`, `APP_FALLBACK_LOCALE`).
3. Magic Alias `Lang` → `translator`.
4. `Machine::langPath()` / `path.lang` for app overrides.
5. Ships Laravel English `validation.php` (and auth/passwords/pagination) under `Translation/lang/en/`.

Pest: `tests/Translation/TranslatorTest.php`.
