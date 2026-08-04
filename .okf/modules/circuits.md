---
type: Module
title: Circuits
description: Integrated-circuit registry/factory surface — config/circuits.php wires drivers; chip implementations live in DOSR packages.
resource: src/Fabricate/Circuits
tags: [modules, circuits]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: dir
    resource: src/Fabricate/Circuits
    title: Circuits module (CircuitRegistry, CircuitsServiceProvider, DataRegister)
  - id: config
    resource: config/circuits.php
    title: Default circuits config
  - id: readme
    resource: README.md
    title: circuits.php for IC drivers and bus parameters
---

# Owns

- `CircuitRegistry`, `CircuitsServiceProvider`, related contracts under `Fabricate\Contracts\Circuits`
- Mergeable `config/circuits.php` shape for IC drivers and bus parameters[^readme]

# Does not own

- Specific chip drivers (TSL2591, SSD1306, PCA9685, …) — those are `dept-of-scrapyard-robotics/*` and document their own parameters
- Low-level I²C/SPI/UART handles — `scrapyard-io/gpio-framework` + `microscrap/*`

# Integration

Chip drivers are implemented and registered in `dept-of-scrapyard-robotics/*` packages, then referenced from the application’s `config/circuits.php`. Chip logic does not belong in `Fabricate\Circuits`.

[^dir]: Circuits module (CircuitRegistry, CircuitsServiceProvider, DataRegister)
[^config]: Default circuits config
[^readme]: circuits.php for IC drivers and bus parameters
