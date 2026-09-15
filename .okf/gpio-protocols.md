---
type: Concept
title: GPIO protocols
description: Protocol adapter managers under gpio.protocols and Workshop about GPIO section.
tags: [gpio, protocols, about]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-15T02:07:33Z" }
sources:
  - id: provider
    resource: src/GeneralPurposeIO/Core/Providers/ScrapyardIOServiceProvider.php
    title: ScrapyardIOServiceProvider
  - id: config
    resource: config/gpio.php
    title: gpio.php
---

# Role

Protocol transports (digital-io, i2c, spi, uart, pwm, analog) live beside Circuits. `ScrapyardIOServiceProvider` aggregates protocol providers (`DigitalServiceProvider`, `I2CServiceProvider`, `SPIServiceProvider`, `UARTServiceProvider`, `PWMServiceProvider`, `AnalogServiceProvider`) **and** `CircuitsServiceProvider`.

# About

`ScrapyardIOServiceProvider` contributes Workshop `about` section **GPIO** via microscrap `InstalledVersions` probes + `/sys/class/pwm` for native PWM.

Catalog ICs are a separate About section owned by Circuits — see [Circuits](circuits.md)#about.

# Related

* [Circuits](circuits.md)
* [packaging.md](packaging.md)
* [known-gaps.md](known-gaps.md)
