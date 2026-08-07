---
type: Module
title: sketches
description: fabricate/sketches — Runner entry (php runner), Pipeline middleware, decision-based Logic Orchestration (Node/Flow), AsyncNode via Concurrency (fiber/pokio).
resource: src/Fabricate/Sketches/
tags: [component, sketches, runner, draft]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T07:00:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: sketches
    resource: src/Fabricate/Sketches/
    title: Sketches package
  - id: contracts
    resource: src/Fabricate/Contracts/Sketches/
    title: Sketch contracts
  - id: config
    resource: config/sketches.php
    title: sketches config
  - id: runner
    resource: src/Fabricate/Sketches/Runner/SketchKernel.php
    title: SketchKernel / Runner
  - id: ownership
    resource: .okf/conventions/magic-aliases.md
    title: MagicAlias / provider ownership
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/sketches`[^root] |
| Path | `src/Fabricate/Sketches/` |
| PHP namespace | `Fabricate\Sketches\` |
| Contracts | `Fabricate\Contracts\Sketches\*` |
| Config | `config/sketches.php` — `load`, `middleware`, `concurrency` |
| Layer role | Domain Sketches + Runner; DefaultProviders registers `SketchesServiceProvider`; AssemblyLine binds `SketchKernel` |

# Http → Runner mirror

| Laravel Http | ScrapyardIO Runner |
|--------------|--------------------|
| Controllers | `app/Runner/Sketches` |
| Middleware | `app/Runner/Middleware` via Pipeline |
| `artisan` | `workshop` (framework commands) |
| — | `php runner` / `php runner {sketch}` |

# How it works

1. **Entry:** `Machine::handleSketch` → `SketchKernel` → Symfony `RunnerInstance` listing/running sketches.
2. **Middleware:** `DispatchSketch` sends `SketchRunContext` through `fabricate/pipeline` (global + per-sketch); destination runs Flow-hosted `SketchRunner`.
3. **Flow:** Decision-based Logic Orchestration — boot node → tick self-loop (`continue`/`stop`); `try/finally` shutdown once. `Node`/`Flow` also run **outside** sketches (Workshop, packages, AI step routers); recommended for sketch decision workflows. App nodes live in `app/Workflows`. Guide: website Digging Deeper **Nodes & Flows**.
4. **AsyncNode:** Uses Concurrency (`sketches.concurrency` default `fiber`; optional `pokio` via suggested `nunomaduro/pokio`).
5. **Discovery:** Convention `app/Runner/Sketches` subclasses of `App\Runner\Sketches\Sketch`; config `sketches.load` + `#[Sketch('name')]`.
6. **Generators:** `workshop make:sketch`, `workshop make:middleware` (Runner namespaces); `workshop make:node` / `make:node --async` → `app/Workflows`.

Pest: `tests/Sketches/*`, `tests/Concurrency/FiberAndPokioDriverTest.php`.

[^root]: Umbrella `replace` → `self.version`
