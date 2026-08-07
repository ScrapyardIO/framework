---
type: Trap
title: Component composer drift
description: Component composer.json files still pin ^0.6 deps, list missing helper files, and reflection requires fabricate/collection (singular).
tags: [trap, composer, components, honesty]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T21:05:00Z" }
status: deprecated
sources:
  - id: nab
    resource: src/Fabricate/NutsAndBolts/composer.json
    title: nuts-and-bolts component manifest
  - id: collections
    resource: src/Fabricate/Collections/composer.json
    title: collections component manifest
  - id: reflection
    resource: src/Fabricate/Reflection/composer.json
    title: reflection component manifest
  - id: root
    resource: composer.json
    title: Umbrella 0.7.0 autoload/replace
---

> **Deprecated:** Angel is restoring component completeness this session. Treat gaps as work-in-progress, not permanent product truth. (Formerly titled “Component composer drift”.)

# Symptoms

Component manifests describe a richer / older packaging world than the umbrella actually ships.[^root]

# Known drifts (verified against files)

## nuts-and-bolts

- Requires Carbon, dotenv, UUID, CommonMark, portable-ascii, Symfony UID/var-dumper, plus `fabricate/macroable` and `fabricate/collections` at `^0.6.0`.[^nab]
- Autoloads `Helpers/functions.php`, `helpers.php`, `bytes.php`, `time.php` — **those files are not in** `src/Fabricate/NutsAndBolts/` on disk.
- On-disk PHP is contracts + `ScrapyardIOException` only.

## collections (partially reconciled)

- Now requires only `fabricate/macroable` + `fabricate/nuts-and-bolts` at `^0.6.0` — `fabricate/contracts` removed.[^collections]
- Still pins those components at `^0.6.0` while the umbrella is `0.7.0`.
- See refreshed [collections](../components/collections.md).

## reflection

- Requires `fabricate/collection` (singular) `^0.6|^0.7` — likely typo vs `fabricate/collections`.[^reflection]
- Component `files` autoload includes `Helpers/helpers.php`, but umbrella `autoload.files` does **not** — global `lazy()` may be missing when only the umbrella is required.[^root]

## branch aliases

Several components still declare `extra.branch-alias` for `0.5.x-dev` / `0.6.x-dev` while the umbrella is `0.7.0`.

# Agent guidance

- Treat **root** `composer.json` + on-disk PHP as source of truth for umbrella consumers.
- Treat component `composer.json` as **aspirational / stale split manifests** until reconciled.
- Do not invent helper APIs (bytes/time/env helpers under NutsAndBolts) from component file lists alone.

[^nab]: nuts-and-bolts component manifest
[^collections]: collections component manifest
[^reflection]: reflection component manifest
[^root]: Umbrella 0.7.0 autoload/replace
