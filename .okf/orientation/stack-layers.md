---
type: Architecture
title: Hardware stack layers
description: Typical composition from sketch/command down through Fabricate APIs to transports and native bindings.
tags: [orientation, architecture, stack]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
status: draft
sources:
  - id: readme-stack
    resource: README.md
    title: Hardware Stack section
---

# Typical conceptual stack (top → bottom)

This is the teaching diagram from the package README — not a required call path for every application. Sketches may use GPIO transports or DOSR drivers directly and omit waveforms/tubes when that composition fits.

```text
Sketch or Workshop command
    ↓
Fabricate APIs (this package: sensors, displays, visuals, actuation registries, …)
    ↓
Waveforms / Tubes abstractions          (scrapyard-io/waveforms, scrapyard-io/tubes)  [optional]
    ↓
Dept. of Scrapyard Robotics chip drivers (dept-of-scrapyard-robotics/*)
    ↓
GPIO Framework transports                 (scrapyard-io/gpio-framework)
    ↓
Native or USB bindings                    (microscrap/*)
```

[^readme-stack]

# Implication for this package

`scrapyard-io/framework` owns the **Fabricate APIs** layer and the application runtime that hosts sketches and Workshop. Transport, chip, and binding concerns are composed from companion packages — see [Ownership boundaries](ownership.md).

[^readme-stack]: Hardware Stack section
