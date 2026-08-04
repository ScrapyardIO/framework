---
type: Convention
title: MagicAlias terminology
description: Canonical pattern is MagicAlias (container-backed static proxy under NutsAndBolts\\MagicAliases); some code still says Facade — treat as migration state, not a rename mandate.
tags: [convention, terminology]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: magic-aliases
    resource: src/Fabricate/NutsAndBolts/MagicAliases
    title: MagicAliases directory (App, Cache, Config, …)
  - id: neo4j
    resource: neo4j://Memory/memory-magic-alias-terminology-2026-07-15
    title: MagicAlias terminology memory
---

# Canonical pattern (current)

Prefer **MagicAlias** for Laravel-Facade-analogue static proxies into the container. Implementations live under `Fabricate\NutsAndBolts\MagicAliases\` and are registered via bootstrap (`RegisterMagicAliases`).[^magic-aliases]

# Migration state (do not over-enforce)

Source still contains **Facade**-flavored references in places (e.g. queue uniqueness / background queue imports mentioning `Fabricate\NutsAndBolts\Facades\...`) even when a matching `Facades` namespace may be absent or incomplete in-tree.

Therefore:

- Teach/use **MagicAlias** for new code and docs.
- Do **not** run broad automated renames assuming every “facade” string is wrong.
- When you see `Facades\` references, treat them as legacy/mixed terminology to reconcile carefully against the MagicAliases directory.

# Conversational pitfall

In Scrapyard talk, someone may say “facade” meaning a fluent collaborator builder — that is **not** the same as MagicAlias. Confirm which meaning before refactoring.[^neo4j]

[^magic-aliases]: MagicAliases directory (App, Cache, Config, …)
[^neo4j]: MagicAlias terminology memory
