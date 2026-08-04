---
type: Convention
title: Ownership boundaries
description: What scrapyard-io/framework owns versus companion packages — stop invasive scaffolding in the wrong tree.
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
| `dept-of-scrapyard-robotics/*` | Concrete chip/device drivers + package docs/params |
| App skeleton (`scrapyard-io` root app) | `app/`, `bootstrap/`, `workshop`, env, composed requires |

# Hard don’ts

- Do not clone CarrierDriverManager-style scaffolding into unrelated domains “to make tests pass”.
- Do not put device-specific OKF under `src/Fabricate/Actuation/...` — device knowledge stays in DOSR package `.okf`.
- Do not treat unfinished native PWM/sysfs drivers as solved by rewriting generic-servos alone — [Wrong layer](../traps/wrong-layer.md).

[^readme]: Framework vs companion packages
