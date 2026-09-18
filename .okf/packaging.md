---
type: Concept
title: Packaging
description: Eight gpio/* splits, Core unsplit, manifest rules as they stand in the eight component composer.json files, dependency direction.
tags: [packaging, composer, splits, dependency-direction]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-18T21:40:00Z" }
sources:
  - id: root
    resource: composer.json
    title: root composer.json
  - id: contracts
    resource: src/GeneralPurposeIO/Contracts/composer.json
    title: gpio/contracts composer.json
  - id: nuts
    resource: src/GeneralPurposeIO/NutsAndBolts/composer.json
    title: gpio/nuts-and-bolts composer.json
  - id: ics
    resource: src/GeneralPurposeIO/IntegratedCircuits/composer.json
    title: gpio/integrated-circuits composer.json
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
---

# Eight splits, one unsplit Core

`src/GeneralPurposeIO/{Contracts,Digital,I2C,IntegratedCircuits,NutsAndBolts,PWM,SPI,UART}` — each own `composer.json`, `.gitattributes`, `LICENSE`, each in root `replace` as `gpio/<name>`. `Core`: none of that. No split, ships only inside the `scrapyard-io/framework` umbrella. Holds `Core\Providers\ScrapyardIOServiceProvider`, `Core\MagicAliases\{GPIO,Circuit}`, and the `gpio` dock resource.

# Rule: requires follow imports

Each manifest's `require` matches what that component's PHP actually imports, not a plan on paper. `gpio/contracts` (45 files) imports `Voyager\Contracts\IOPools\{IOResourceDriver,Occurrence,Completion}` and the concrete `Voyager\IOPools\Presumption` (the return type of `GPIOResourceDriver::defer()`), so it requires `venusian-voyager/io-pools` on top of `venusian-voyager/contracts`.

# Rule: hardware is suggest only, and it is the adapters now

No component `require`s a hardware package — always `suggest`, so a posix-only box never drags `ext-ftdi` and an ext-free box (CI, most dev machines) still installs. What a protocol component suggests is the **adapter**, `microscrap/scrapyard-linux` and/or `microscrap/scrapyard-usb`, not the low-level `microscrap/{gpio,i2c,spi,uart,posix,ftdi,mpsse}` bindings those adapters sit on. The adapters carry the bindings.

# Rule: each protocol component declares its own provider and alias

`extra.venusian` on the component's own manifest, not just the umbrella's:

| Package | Provider | Alias |
|---|---|---|
| `gpio/digital` | `GeneralPurposeIO\Digital\DigitalIOServiceProvider` | `DigitalIO` |
| `gpio/i2c` | `GeneralPurposeIO\I2C\I2CServiceProvider` | `I2C` |
| `gpio/spi` | `GeneralPurposeIO\SPI\SPIServiceProvider` | `SPI` |
| `gpio/uart` | `GeneralPurposeIO\UART\UARTServiceProvider` | `UART` |
| `gpio/pwm` | `GeneralPurposeIO\PWM\PWMServiceProvider` | `PWM` |
| `gpio/integrated-circuits` | `GeneralPurposeIO\IntegratedCircuits\IntegratedCircuitsServiceProvider` | — (the `Circuit` alias is in unsplit Core, like `GPIO`) |
| `gpio/contracts`, `gpio/nuts-and-bolts` | none — no provider, no bindings of their own | — |

Aggregate `Core\Providers\ScrapyardIOServiceProvider` (root manifest only) lists the five protocol providers plus the integrated-circuits one, and registers the `gpio` dock resource in `boot()`.

# Requires as they now stand, per package

| Package | `require` (besides `php`) | `suggest` |
|---|---|---|
| `gpio/contracts` | `venusian-voyager/contracts`, `venusian-voyager/io-pools` | — |
| `gpio/nuts-and-bolts` | `gpio/contracts` | — |
| `gpio/integrated-circuits` | `gpio/contracts`, `venusian-voyager/contracts`, `venusian-voyager/nuts-and-bolts` | — |
| `gpio/digital` | `gpio/contracts`, `venusian-voyager/collections`, `venusian-voyager/contracts`, `venusian-voyager/magic-aliases`, `venusian-voyager/nuts-and-bolts` | `microscrap/scrapyard-linux`, `microscrap/scrapyard-usb` |
| `gpio/i2c` | the digital set, plus `gpio/nuts-and-bolts` | `microscrap/scrapyard-linux`, `microscrap/scrapyard-usb` |
| `gpio/spi` | the digital set | `microscrap/scrapyard-linux`, `microscrap/scrapyard-usb` |
| `gpio/uart` | the digital set, plus `gpio/nuts-and-bolts` | `microscrap/scrapyard-linux`, `microscrap/scrapyard-usb` |
| `gpio/pwm` | the digital set | `microscrap/scrapyard-linux` |

No protocol requires another protocol. 0.7's `i2c`/`spi` → `digital` edge is gone: shared pin work moved into the adapters, which require every `gpio/*` protocol themselves and so sit above all of them.

`i2c` and `uart` reach `gpio/nuts-and-bolts` for the `bytes2array` / `array2bytes` helpers.

Root umbrella `composer.json`: `require.venusian/framework: ^0.8.0`, `replace` = the eight `gpio/*` at `self.version`, `require-dev` pulls seven `microscrap/*` bindings at `^0.8.0` + `pestphp/pest ^4`, `suggest` mirrors that binding set. See [known-gaps.md](known-gaps.md) — that `require-dev` is why the suite does not install standalone.

# Dependency direction (recap, see AGENTS.md)

Contracts imports `Voyager\Contracts`, the concrete `Voyager\IOPools\Presumption`, and microscrap DTOs. Components import Contracts, NutsAndBolts, Collections, MagicAliases, microscrap. Nothing below Core imports Core. No **split** touches `Voyager\System` any more: the one import left, `Voyager\System\Console\AboutCommand` in `Core\Providers\ScrapyardIOServiceProvider`, is in unsplit Core, which ships only with the umbrella and so may require the framework. In 0.7 it was `gpio/circuits` reaching System through a guarded `suggest`; that component is gone.

# Rule: runtime still needs a Venusian application

Splitting `gpio/*` apart from `venusian/framework` only frees the *installable* dependency. At runtime every protocol provider calls the `config()` / `app()` helpers, which live in unsplit `venusian/framework` System — so any `gpio/*` package still needs a booted Venusian application to run, even though the umbrella, not each split, is what `require`s the framework.

# Related

* [known-gaps.md](known-gaps.md)
* [overview.md](overview.md)
* [integrated-circuits.md](integrated-circuits.md)
