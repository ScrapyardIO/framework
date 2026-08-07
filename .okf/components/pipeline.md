---
type: Module
title: pipeline
description: fabricate/pipeline — onion pipes; Core owns Pipeline MagicAlias + PipelineServiceProvider (`pipeline`, Hub).
resource: src/Fabricate/Pipeline/
tags: [component, pipeline, draft]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T06:05:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: pipeline
    resource: src/Fabricate/Pipeline/Pipeline.php
    title: Pipeline
  - id: hub
    resource: src/Fabricate/Pipeline/Hub.php
    title: Hub
  - id: composer
    resource: src/Fabricate/Pipeline/composer.json
    title: fabricate/pipeline package
  - id: provider
    resource: src/Fabricate/Core/Providers/PipelineServiceProvider.php
    title: Core PipelineServiceProvider
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Pipeline.php
    title: Pipeline MagicAlias
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
| Composer | `fabricate/pipeline`[^composer][^root] |
| Path | `src/Fabricate/Pipeline/` |
| PHP namespace | `Fabricate\Pipeline\` |
| Contracts | `Fabricate\Contracts\Pipeline\*` |
| Package files | `composer.json`, `LICENSE.md`, `.gitattributes` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain Pipeline/Hub; Core binds `pipeline` + Hub + Magic Alias[^ownership] |

# How it works

1. `Pipeline::send($passable)->through($pipes)->then($destination)` (or `thenReturn()`).
2. Pipes may be callables, objects, or container class strings (`Class:param`).
3. Core `PipelineServiceProvider` (deferred) binds `pipeline` + `Contracts\Pipeline\Hub`.
4. Magic Alias `Pipeline` → `pipeline` with `$cached = false` (fresh instance per call).
5. `withinTransaction()` needs `db` / Database — not restored in 0.7; API kept for parity.

Pest: `tests/Pipeline/PipelineTest.php`.

[^composer]: fabricate/pipeline package
[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
