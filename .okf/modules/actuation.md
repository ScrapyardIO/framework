---
type: Module
title: Actuation
description: Framework actuator registry and base types for fans, servos, human input — concrete devices stay in DOSR packages.
resource: src/Fabricate/Actuation
tags: [modules, actuation, fans, servos]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
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
- Subnamespaces: `Fans`, `Servos`, `HumanInput` — framework contracts and bases, not concrete chip packages[^dir]
- `config/actuators.php`

# Does not own

- Concrete device packages such as `dept-of-scrapyard-robotics/generic-servos`, `generic-fans`, `generic-buttons`
- PWM and other transport implementations (`scrapyard-io/gpio-framework`)
- Native driver bindings (`microscrap/*`)

Applications compose DOSR device packages with GPIO transports and register actuators through this module’s registry APIs. See [Ownership boundaries](../orientation/ownership.md) and [Hardware stack layers](../orientation/stack-layers.md).

[^dir]: Actuator, ActuatorRegistry, Fans, Servos, HumanInput
[^config]: Default actuators config
