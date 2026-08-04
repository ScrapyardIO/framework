---
type: Trap
title: Wrong layer
description: Classic ScrapyardIO agent failure — rewriting a DOSR device or Fabricate module when the blocker is an unfinished native/gpio carrier.
tags: [trap, ownership]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: pwm
    resource: neo4j://Memory/memory-2026-07-11-native-pwm-unfinished
    title: Native PWM unfinished stub
  - id: servos
    resource: neo4j://Memory/memory-2026-07-11-generic-servos-pattern-gap
    title: generic-servos missed DOSR pattern
---

# Symptom

Hours spent on generic-servos / Actuation / RealityInterface-shaped rewrites while hardware still cannot PWM.

# Cause

[Stack layers](../orientation/stack-layers.md) were skipped. Native PWM driver methods were TODO; DOSR pattern expects app-built GPIO transports + BareMetal-style device classes.[^pwm][^servos]

# Fix approach

1. Name the layer of the failure.
2. Open that package (and its `.okf` if present).
3. Only then change framework registries/APIs if the contract itself is wrong.

[^pwm]: Native PWM unfinished stub
[^servos]: generic-servos missed DOSR pattern
