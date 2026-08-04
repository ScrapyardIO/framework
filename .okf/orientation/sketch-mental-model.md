---
type: Concept
title: Sketch mental model
description: A sketch is a foreground cooperative workload with boot → loop → shutdown lifecycle, discovered as Workshop commands.
tags: [orientation, sketches]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: sketch
    resource: src/Fabricate/Sketches/Sketch.php
    title: Abstract Sketch base
  - id: readme
    resource: README.md
    title: Your First Sketch
---

# Lifecycle

1. `boot()` — prepare resources  
2. `loop(): SketchLoopResult` — one cooperative unit of work (**abstract**)  
3. `shutdown()` — release resources (also after exceptions)[^sketch]

Return `SketchLoopResult::CONTINUE` for another tick or `SketchLoopResult::STOP` to finish. With `pcntl`, `SIGINT`/`SIGTERM` request cooperative stop so `shutdown()` can run.[^readme]

# Discovery

- App sketches live in `app/Sketches` (extend app `Sketch` extending `Fabricate\Sketches\Sketch`)
- Class name → kebab-case Workshop command: `HelloHardware` → `php workshop sketch hello-hardware`
- `php workshop sketch:list` / `php workshop make:sketch Name`

# Mental model

Sketches are **not** HTTP controllers. They are long-lived (or single-tick) hardware/console workloads managed by [SketchRunner](../core/workshop.md) under the [Machine](../core/machine.md).

[^sketch]: Abstract Sketch base
[^readme]: Your First Sketch
