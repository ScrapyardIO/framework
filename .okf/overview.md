---
type: Framework
title: scrapyard-io/framework overview
description: What ships at 0.10.0 — nine dirs, eight splits, file counts, stack position, port lineage.
tags: [overview, tree, stack]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-15T04:00:00Z" }
revised: { by: claude-opus-5-5/claude-code, at: "2026-10-04T23:40:00Z", note: "Core gains the scrapyard:ext and scrapyard:modules dev commands; counts rechecked" }
sources:
  - id: tree
    resource: src/GeneralPurposeIO
    title: src/GeneralPurposeIO tree
  - id: root-composer
    resource: composer.json
    title: root composer.json
  - id: spec
    resource: docs/superpowers/specs/2026-09-23-scrapyard-io-0.9-design.md
    title: 0.9 design spec
---

# What ships

0.10.0 GPIO framework for Venusian. Nine dirs under `src/GeneralPurposeIO`: Contracts, Core, Digital, I2C, IntegratedCircuits, NutsAndBolts, PWM, SPI, UART. Eight split as `gpio/*` packages; `Core` not split. 115 PHP files total (`find src -name '*.php' | wc -l`).

Per-dir count:

| Dir | Files |
|---|---|
| Contracts | 44 |
| Core | 20 |
| Digital | 8 |
| I2C | 8 |
| IntegratedCircuits | 5 |
| NutsAndBolts | 6 |
| PWM | 8 |
| SPI | 9 |
| UART | 7 |

Requires `venusian-voyager/{io-pools,contracts,collections,nuts-and-bolts,console,process,workflows} ^0.10.0` — components, not `venusian/framework` — plus `laravel/prompts`, `symfony/{filesystem,process}` and `composer-runtime-api` for the dev commands. PHP `^8.4|^8.5|^8.6`. Namespace root `GeneralPurposeIO\`.

Core is the aggregate `ScrapyardIOServiceProvider` and the two machine-setup dev commands it registers ([dev-commands.md](dev-commands.md)): it merges `config/gpio.php` (protocol defaults only) and aggregates the five protocol providers plus `IntegratedCircuitsServiceProvider`. No MagicAliases — managers are reached by container key (`gpio.i2c`, `gpio.spi`, `gpio.uart`, `gpio.digital`, `gpio.pwm`, `circuit`). No `gpio` dock resource: the transports ride the IOPools `Loop` directly (see [gpio-protocols.md](gpio-protocols.md)).

# Stack position

`ext-posi` / `ext-ftdi` (1:1 syscalls) → `microscrap/*` (libgpiod / libmpsse / spidev / termios in PHP) → `microscrap/scrapyard-{linux,usb}` (the `native` and `usb` drivers) → **`scrapyard-io/framework`** (protocol managers, transports, loop waits and `via()`, chip vocabulary and catalog) → `dept-of-scrapyard-robotics/*` (chip drivers).

House rules: `.okf` at package root only, split packages own `composer.json`, contracts mirror components, exceptions descend one root, enums UPPERCASE, `is_null()`, Pest v4, strong types.

# Port lineage

0.7.0 `scrapyard-io/gpio-framework` → 0.8 on `venusian/framework` 0.8 (`docs/superpowers/specs/2026-09-14-gpio-framework-0-8-port-design.md`) → 0.9 on the `venusian-voyager/*` components, blocking calls restored and loop / `via()` paths added protocol by protocol (`docs/superpowers/specs/2026-09-23-scrapyard-io-0.9-design.md`) → 0.10 on the `venusian-voyager/*` 0.10 components: `via()` offloads to worker pools, watches wake on their streams.

# CI

`.github/workflows/tests.yml`: ubuntu, PHP 8.4 and 8.5, `composer update --prefer-stable`, `vendor/bin/pest`. No extensions — the suite runs on fakes.

# Related

* [gpio-protocols.md](gpio-protocols.md)
* [integrated-circuits.md](integrated-circuits.md)
* [display-panels.md](display-panels.md)
* [packaging.md](packaging.md)
