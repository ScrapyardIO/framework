---
type: Convention
title: Ownership boundaries
description: What scrapyard-io/framework owns versus companion packages in the published composition model.
tags: [orientation, ownership]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Framework vs companion packages
---

# This package owns

- `Fabricate\` application runtime ([Machine](../core/machine.md), [Chassis](../core/chassis.md), providers)
- Sketch lifecycle ([Sketch mental model](sketch-mental-model.md))
- Registries and framework-level APIs for circuits, sensors, displays, fonts, framebuffers, actuation, rendering
- App services: config, events, cache, filesystem, log, queue, Redis, process, console/Workshop
- Default `config/*.php` shipped with the framework (merged with app config)

# Companion packages own

| Package family | Owns |
|----------------|------|
| `microscrap/*` | Low-level bindings (posix, gpio, i2c, spi, uart, ftdi, mpsse, sdl3, glfw, …) |
| `scrapyard-io/gpio-framework` | Transport APIs (digital, I²C, SPI, UART, PWM, analog) |
| `scrapyard-io/waveforms` | Higher-level sensor abstractions over transports |
| `scrapyard-io/tubes` | Display-panel abstractions |
| `dept-of-scrapyard-robotics/*` | Concrete chip/device drivers and their documented parameters |
| Application project | `app/`, `bootstrap/`, `workshop`, env, Composer requires that compose the stack |

# Boundaries

- Protocol adapters and device drivers are **not** this package — compose them from companions.[^readme]
- Device-specific knowledge belongs with the device package, not under `src/Fabricate/Actuation/...` or other framework module trees.
- Circuit parameters are documented by each `dept-of-scrapyard-robotics/*` package (see README Configuration section).

[^readme]: Framework vs companion packages
