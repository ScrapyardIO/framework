---
type: CoreType
title: AssemblyLine
description: Fabricate\\Core\\Setup\\AssemblyLine — fluent bootstrap builder attached to Machine::configure() for kernels, events, commands, providers.
resource: src/Fabricate/Core/Setup/AssemblyLine.php
tags: [core, bootstrap]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
status: draft
sources:
  - id: assembly
    resource: src/Fabricate/Core/Setup/AssemblyLine.php
    title: AssemblyLine class
---

# Role

Builder that wires standard pieces onto a [Machine](machine.md) before `create()`:

- `withKernels()` — bind ConsoleKernel
- `withEvents(...)` — event service provider / discovery paths
- `withCommands(...)` — register additional console commands
- Provider pending registration helpers (see source for full API)[^assembly]

# Extension point

Extend bootstrap through AssemblyLine and service providers rather than ad-hoc globals. The application provider list lives in `bootstrap/providers.php`.

[^assembly]: AssemblyLine class
