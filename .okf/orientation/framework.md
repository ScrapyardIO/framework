---
type: Package
title: Framework package
description: scrapyard-io/framework — PHP application runtime for hardware-facing programs (Fabricate namespace, v0.6.x).
resource: .
tags: [orientation, framework, fabricate]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Package README
  - id: composer
    resource: composer.json
    title: Package name, version, replace map
---

# What it is

The ScrapyardIO **application framework**: dependency injection, configuration, package discovery, Workshop console, lifecycle-managed sketches, hardware registries (circuits/sensors/displays/fonts/framebuffers), rendering, and supporting services (cache, filesystem, log, queue, Redis, events, process).[^readme]

Public PHP namespace: `Fabricate\`. Composer name: `scrapyard-io/framework`. Version constant: `Machine::VERSION` (`0.6.0` as of this draft).[^composer]

# What it is not

- Not a chip driver package (those are `dept-of-scrapyard-robotics/*`)
- Not native/USB bindings (`microscrap/*`)
- Not the GPIO transport package (`scrapyard-io/gpio-framework`)
- Not waveforms/tubes abstractions (`scrapyard-io/waveforms`, `scrapyard-io/tubes`)
- Not an application skeleton by itself — apps wire `bootstrap/app.php` + `workshop` (see skeleton `ScrapyardIO/scrapyard-io`)

# Agent entry

| Need | Concept |
|------|---------|
| Where code belongs | [Ownership](ownership.md) |
| Stack diagram | [Hardware stack layers](stack-layers.md) |
| Boot path | [Machine](../core/machine.md), [Workshop](../core/workshop.md) |
| Workload unit | [Sketch mental model](sketch-mental-model.md) |

[^readme]: Package README
[^composer]: Package name, version, replace map
