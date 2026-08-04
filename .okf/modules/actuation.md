---
type: Module
title: Actuation
description: Framework actuator registry and base types for fans, servos, human input — concrete devices stay in DOSR packages.
resource: src/Fabricate/Actuation
tags: [modules, actuation, fans, servos]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: dir
    resource: src/Fabricate/Actuation
    title: Actuator, ActuatorRegistry, Fans, Servos, HumanInput
  - id: config
    resource: config/actuators.php
    title: Default actuators config
---

# Owns

- `Actuator`, `ActuatorRegistry`, `ActuationServiceProvider`
- Subnamespaces: `Fans`, `Servos`, `HumanInput` — **framework contracts/bases**, not every generic device package[^dir]
- `config/actuators.php`

# Does not own

- `dept-of-scrapyard-robotics/generic-servos`, `generic-fans`, `generic-buttons`, etc.
- PWM sysfs / native driver completion — that is gpio-framework + microscrap/native-drivers territory

# Agent rule (hard-won)

If a servo/fan integration fails, verify **transport + native driver** and **DOSR integration pattern** (`::pwm()` / GPIO contracts, BareMetal bases) before rewriting Actuation framework types. See [Wrong layer](../traps/wrong-layer.md) and Neo4j memories on generic-servos / native PWM.

[^dir]: Actuator, ActuatorRegistry, Fans, Servos, HumanInput
[^config]: Default actuators config
