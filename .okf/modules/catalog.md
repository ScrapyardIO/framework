---
type: Catalog
title: Module catalog
description: Top-level Fabricate domains inside scrapyard-io/framework — registries and APIs, not chip drivers.
tags: [modules, catalog]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: src
    resource: src/Fabricate
    title: Fabricate domain directories
  - id: readme
    resource: README.md
    title: Framework Capabilities
---

# Hardware / graphics domains

| Module | Path | Concept |
|--------|------|---------|
| Circuits | `Fabricate\Circuits` | [Circuits](circuits.md) |
| Sensors | `Fabricate\Sensors` | [Sensors](sensors.md) |
| Displays | `Fabricate\Displays` | [Displays](displays.md) |
| Rendering | `Fabricate\Rendering` | [Rendering](rendering.md) |
| Framebuffers | `Fabricate\Framebuffers` | covered under Rendering |
| Fonts | `Fabricate\Fonts` | covered under Rendering |
| Actuation | `Fabricate\Actuation` | [Actuation](actuation.md) |
| UX | `Fabricate\UX` | input/layout helpers (see catalog notes) |

# Application domains

Core runtime + [Supporting services](supporting-services.md): Bus, Cache, Chassis, Collections, Conditionable, Config, Console, Contracts, Database, Encryption, Events, Filesystem, Log, Macroable, NutsAndBolts, Pipeline, Process, Queue, Redis, Reflection, Sketches, Testing.

# Ownership reminder

These modules define **framework surfaces**. Concrete chips and bindings live elsewhere — [Ownership](../orientation/ownership.md).

[^src]: Fabricate domain directories
[^readme]: Framework Capabilities
