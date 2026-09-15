---
type: Concept
title: Packaging
description: Nine gpio/* splits, Core unsplit, manifest rules as they stand in the nine component composer.json files, dependency direction.
tags: [packaging, composer, splits, dependency-direction]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-15T04:00:00Z" }
sources:
  - id: root
    resource: composer.json
    title: root composer.json
  - id: contracts
    resource: src/GeneralPurposeIO/Contracts/composer.json
    title: gpio/contracts composer.json
  - id: common
    resource: src/GeneralPurposeIO/Common/composer.json
    title: gpio/common composer.json
  - id: digital
    resource: src/GeneralPurposeIO/Digital/composer.json
    title: gpio/digital composer.json
  - id: i2c
    resource: src/GeneralPurposeIO/I2C/composer.json
    title: gpio/i2c composer.json
  - id: spi
    resource: src/GeneralPurposeIO/SPI/composer.json
    title: gpio/spi composer.json
  - id: uart
    resource: src/GeneralPurposeIO/UART/composer.json
    title: gpio/uart composer.json
  - id: pwm
    resource: src/GeneralPurposeIO/PWM/composer.json
    title: gpio/pwm composer.json
  - id: analog
    resource: src/GeneralPurposeIO/Analog/composer.json
    title: gpio/analog composer.json
  - id: circuits
    resource: src/GeneralPurposeIO/Circuits/composer.json
    title: gpio/circuits composer.json
---

# Nine splits, one unsplit Core

`src/GeneralPurposeIO/{Analog,Circuits,Common,Contracts,Digital,I2C,PWM,SPI,UART}` — each own `composer.json`, `.gitattributes`, `LICENSE`, each in root `replace` map as `gpio/<name>`. `Core`: none of that. No split, ships only inside `scrapyard-io/framework` umbrella. Holds aggregate `Core\Providers\ScrapyardIOServiceProvider`, `Core\MagicAliases\{GPIO,Circuit}`, and the `gpio` dock resource.

# Rule: requires follow imports

Each manifest's `require` matches what component's PHP actually imports, not a plan on paper. Every component, `gpio/contracts` included, requires `venusian-voyager/contracts` — each reaches `Voyager\Contracts\...` symbols somewhere (`Vessel`, `NutsAndBolts\Contracts\...`). `gpio/contracts` (48 files) also imports `Voyager\Contracts\IOPools\{IOResourceDriver,Occurrence,Completion}` and the concrete `Voyager\IOPools\Presumption` (the return type of `GPIOResourceDriver::defer()`), so its `require` also carries `venusian-voyager/io-pools`.

# Rule: microscrap is suggest only

No component `require`s a `microscrap/*` package — always `suggest`. Keeps posix-only install from dragging `ext-ftdi`; keeps ext-free install (CI, most dev boxes) installable at all. `gpio/contracts` suggests `microscrap/gpio` for `GPIOLineRequest` DTO type used in `DigitalIODriver`.

# Rule: gpio/circuits suggests venusian/framework, never requires it

`Circuits` imports `Voyager\System\Application` and `Voyager\System\Console\AboutCommand` — both live in unsplit `venusian/framework` System, not any split Voyager sub-package. Requiring `venusian/framework` from a split component would invert dependency direction (nothing under `Core` may need System). Both uses safe absent the package: `instanceof Application` on a class that doesn't exist is `false`, not fatal; About registration guarded by `class_exists(AboutCommand::class)` before ever calling `AboutCommand::add`. So: `"suggest": {"venusian/framework": "^0.8.0 - Workshop about rows and config publishing"}`.

# Rule: each protocol component declares its own provider

`extra.venusian.providers` on the component's own manifest, not just the umbrella's:

| Package | Provider |
|---|---|
| `gpio/digital` | `GeneralPurposeIO\Digital\DigitalServiceProvider` |
| `gpio/i2c` | `GeneralPurposeIO\I2C\I2CServiceProvider` |
| `gpio/spi` | `GeneralPurposeIO\SPI\SPIServiceProvider` |
| `gpio/uart` | `GeneralPurposeIO\UART\UARTServiceProvider` |
| `gpio/pwm` | `GeneralPurposeIO\PWM\PWMServiceProvider` |
| `gpio/analog` | `GeneralPurposeIO\Analog\AnalogServiceProvider` |
| `gpio/circuits` | `GeneralPurposeIO\Circuits\CircuitsServiceProvider` |
| `gpio/common`, `gpio/contracts` | none — no provider, no bindings of their own |

Aggregate `Core\Providers\ScrapyardIOServiceProvider` (root manifest only) lists all seven protocol/circuit providers plus the `gpio` manager singleton and dock registration.

# Requires as they now stand, per package

| Package | `require` (besides `php`) | `suggest` |
|---|---|---|
| `gpio/contracts` | `venusian-voyager/contracts`, `venusian-voyager/io-pools` | `microscrap/gpio` |
| `gpio/common` | `gpio/contracts`, `venusian-voyager/contracts` | — |
| `gpio/digital` | `gpio/common`, `gpio/contracts`, `venusian-voyager/contracts`, `venusian-voyager/magic-aliases`, `venusian-voyager/nuts-and-bolts` | `microscrap/gpio`, `microscrap/mpsse`, `scrapyard-io/framework` |
| `gpio/i2c` | `gpio/common`, `gpio/contracts`, `gpio/digital`, `venusian-voyager/console`, `venusian-voyager/contracts`, `venusian-voyager/magic-aliases`, `venusian-voyager/nuts-and-bolts` | `microscrap/i2c`, `microscrap/gpio`, `microscrap/mpsse` |
| `gpio/spi` | `gpio/common`, `gpio/contracts`, `gpio/digital`, `venusian-voyager/contracts`, `venusian-voyager/magic-aliases`, `venusian-voyager/nuts-and-bolts` | `microscrap/spi`, `microscrap/gpio`, `microscrap/mpsse` |
| `gpio/uart` | `gpio/common`, `gpio/contracts`, `venusian-voyager/contracts`, `venusian-voyager/magic-aliases`, `venusian-voyager/nuts-and-bolts` | `microscrap/uart`, `microscrap/ftdi` |
| `gpio/pwm` | `gpio/common`, `gpio/contracts`, `venusian-voyager/contracts`, `venusian-voyager/magic-aliases`, `venusian-voyager/nuts-and-bolts` | — |
| `gpio/analog` | `gpio/common`, `gpio/contracts`, `gpio/digital`, `gpio/i2c`, `venusian-voyager/contracts`, `venusian-voyager/magic-aliases`, `venusian-voyager/nuts-and-bolts` | — |
| `gpio/circuits` | `gpio/contracts`, `venusian-voyager/console`, `venusian-voyager/contracts`, `venusian-voyager/nuts-and-bolts` | `venusian/framework` |

`i2c` and `spi` both require `gpio/digital` — shared connections since 0.6, digital-io underlies both buses. `analog` requires both `gpio/digital` and `gpio/i2c`, same reason (ADC channels ride both).

Root umbrella `composer.json`: `require.venusian/framework: ^0.8.0`, `replace` = nine `gpio/*` at `self.version`, `require-dev` pulls all seven `microscrap/*` at `^0.8.0` + `pestphp/pest ^4`, `suggest` mirrors `require-dev`'s microscrap set with per-package notes.

# Dependency direction (recap, see AGENTS.md)

Contracts imports `Voyager\Contracts`, the concrete `Voyager\IOPools\Presumption`, and microscrap DTOs. Components import Contracts, Common, NutsAndBolts, MagicAliases, microscrap. Nothing below Core imports Core. Circuits is the one component touching System — via `suggest`, guarded, never `require`.

# Rule: runtime still needs a Venusian application

Splitting `gpio/*` apart from `venusian/framework` only frees the *installable* dependency at the package level. At runtime every protocol provider and `GPIOProtocolManager::protocol()` call the `config()` / `app()` helpers, which live in unsplit `venusian/framework` System — so any of the nine `gpio/*` packages still needs a booted Venusian application to actually run, even though the umbrella (not each split) is the one that `require`s `venusian/framework`.

# Related

* [known-gaps.md](known-gaps.md)
* [overview.md](overview.md)
