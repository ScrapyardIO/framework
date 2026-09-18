---
type: Framework
title: scrapyard-io/framework overview
description: What ships at 0.8.0 — nine dirs, eight splits, file counts, stack position, port lineage.
tags: [overview, tree, stack]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-15T04:00:00Z" }
sources:
  - id: tree
    resource: src/GeneralPurposeIO
    title: src/GeneralPurposeIO tree
  - id: root-composer
    resource: composer.json
    title: root composer.json
  - id: spec
    resource: docs/superpowers/specs/2026-09-14-gpio-framework-0-8-port-design.md
    title: 0.8 port design spec
---

# What ships

0.8.0 GPIO framework for Venusian. Nine dirs under `src/GeneralPurposeIO`: Contracts, Core, Digital, I2C, IntegratedCircuits, NutsAndBolts, PWM, SPI, UART. Eight split as `gpio/*` packages; `Core` not split. 104 PHP files total (`find src -name '*.php' | wc -l`).

Per-dir count:

| Dir | Files |
|---|---|
| Contracts | 50 |
| Core | 4 |
| Digital | 8 |
| I2C | 7 |
| IntegratedCircuits | 11 |
| NutsAndBolts | 3 |
| PWM | 7 |
| SPI | 7 |
| UART | 7 |

Requires `venusian/framework ^0.8.0`. PHP `^8.4|^8.5|^8.6`. Namespace root `GeneralPurposeIO\`.

Analog, Circuits and Common were dropped on the way to the published 0.8.0 splits. Analog was never a working transport; Circuits was renamed `IntegratedCircuits`, which now carries both the taxonomy and the catalog; Common's `Bytes` moved to `NutsAndBolts`.

# Stack position

`ext-posi` / `ext-ftdi` (1:1 syscalls) → `microscrap/*` (libgpiod / libmpsse / spidev / termios reimplemented in PHP) → **`scrapyard-io/framework`** (protocol managers, buses, chip vocabulary and catalog, `gpio` dock resource) → `dept-of-scrapyard-robotics/*` (chip drivers) → `venusian/surface` `EmbeddedDisplays`.

First-class module beside Surface, same house rules: `.okf` at package root only, split packages own `composer.json`, contracts mirror components, exceptions descend one root, enums UPPERCASE, no class constants, `is_null()`, Pest v4, strong types.

# Port lineage

Ported from `scrapyard-io/gpio-framework` 0.7.0 onto `venusian/framework` 0.8. Design and packaging rationale: `docs/superpowers/specs/2026-09-14-gpio-framework-0-8-port-design.md`.

# Related

* [gpio-protocols.md](gpio-protocols.md)
* [integrated-circuits.md](integrated-circuits.md)
* [display-panels.md](display-panels.md)
* [packaging.md](packaging.md)
* [known-gaps.md](known-gaps.md)
