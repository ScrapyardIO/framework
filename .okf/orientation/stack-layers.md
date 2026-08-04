---
type: Architecture
title: Hardware stack layers
description: Canonical layering from sketch/command down to native bindings — agents must edit the correct layer.
tags: [orientation, architecture, stack]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: readme-stack
    resource: README.md
    title: Hardware Stack section
---

# Typical conceptual stack (top → bottom)

This is the **usual teaching diagram** from the README — not a guaranteed call path for every app. Sketches may talk to GPIO transports or DOSR drivers directly and skip waveforms/tubes when that composition fits.

```text
Sketch or Workshop command
    ↓
Fabricate APIs (this package: sensors, displays, visuals, actuation registries, …)
    ↓
Waveforms / Tubes abstractions          (scrapyard-io/waveforms, scrapyard-io/tubes)  [optional middle]
    ↓
Dept. of Scrapyard Robotics chip drivers (dept-of-scrapyard-robotics/*)
    ↓
GPIO Framework transports                 (scrapyard-io/gpio-framework)
    ↓
Native or USB bindings                    (microscrap/*)
```

[^readme-stack]

# How to use this as an agent

1. Identify the symptom’s layer (e.g. PWM sysfs stub vs servo class API) from the **actual dependencies in play**, not only this diagram.
2. Open that package’s `.okf` / source — do not “fix” a higher layer by reinventing the lower one.
3. DOSR devices typically: extend BareMetal-style bases, take GPIO contract transports built by the app — see Neo4j lessons on generic-servos pattern gaps.

# Related

- [Ownership boundaries](ownership.md)
- [Wrong layer](../traps/wrong-layer.md)

[^readme-stack]: Hardware Stack section
