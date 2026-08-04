---
type: Module
title: Sensors
description: Sensor abstraction registry backed by circuits — framework API over chip drivers.
resource: src/Fabricate/Sensors
tags: [modules, sensors]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
status: draft
sources:
  - id: dir
    resource: src/Fabricate/Sensors
    title: Sensor, SensorRegistry, SensorsServiceProvider
  - id: config
    resource: config/sensors.php
    title: Default sensors config
---

# Owns

- `Sensor`, `SensorRegistry`, `SensorsServiceProvider`
- `config/sensors.php` — sensor abstractions backed by circuits[^config]

# Stack position

Sketch → **Sensors API (here)** → waveforms (optional) → DOSR driver → GPIO → microscrap.[^dir]

Higher-level sensor helpers may also live in `scrapyard-io/waveforms` — check that package’s `.okf` before duplicating abstractions in framework.

[^dir]: Sensor, SensorRegistry, SensorsServiceProvider
[^config]: Default sensors config
