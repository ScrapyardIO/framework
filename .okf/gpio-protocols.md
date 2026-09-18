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

Five protocol transports: digital-io, i2c, spi, uart, pwm. `ScrapyardIOServiceProvider` aggregates their five providers (`DigitalIOServiceProvider`, `I2CServiceProvider`, `SPIServiceProvider`, `UARTServiceProvider`, `PWMServiceProvider`) and registers the `gpio` dock resource in `boot()`. Analog and Circuits used to be in that list; both are gone.

# About

`ScrapyardIOServiceProvider::registerAboutSection()` would contribute Workshop `about` section **GPIO** via microscrap `InstalledVersions` probes plus `/sys/class/pwm` for native PWM. The call in `boot()` is commented out, so no About section ships; the probes also still name the low-level `microscrap/{gpio,i2c,spi,uart,ftdi,mpsse,posix}` packages rather than the `scrapyard-linux` / `scrapyard-usb` adapters the components now suggest.

There is no second About section. The catalog one belonged to Circuits — see [integrated-circuits.md](integrated-circuits.md).

# Related

* [integrated-circuits.md](integrated-circuits.md)
* [packaging.md](packaging.md)
* [known-gaps.md](known-gaps.md)
