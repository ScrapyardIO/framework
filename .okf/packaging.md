---
type: Concept
title: Packaging
description: Eight gpio/* splits, Core unsplit, manifest rules as they stand in the eight component composer.json files, dependency direction.
tags: [packaging, composer, splits, dependency-direction]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-18T21:40:00Z" }
revised: { by: claude-opus-5-5/claude-code, at: "2026-10-04T23:40:00Z", note: "Core holds the dev commands; root requires console, process, workflows. Before that, 0.10 manifests: voyager ^0.10.0; only digital and uart require io-pools (their watches extend WakeSource), the rest import contracts only. Before that, 0.9 manifests: voyager components, no aliases, no dock; digital requires nuts-and-bolts" }
sources:
  - id: router
    resource: https://github.com/arduino/arduino-router-bridge-py
    title: arduino-router-bridge-py (Arduino's MessagePack-RPC client for the router)
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

`src/GeneralPurposeIO/{Contracts,Digital,I2C,IntegratedCircuits,NutsAndBolts,PWM,SPI,UART}` — each own `composer.json`, `.gitattributes`, `LICENSE`, each in root `replace` as `gpio/<name>`. `Core`: none of that. No split, ships only inside the `scrapyard-io/framework` umbrella. Holds `Core\Providers\ScrapyardIOServiceProvider` and the `scrapyard:ext` / `scrapyard:modules` dev commands with their flows ([dev-commands.md](/dev-commands.md)).

# Rule: requires follow imports

Each manifest's `require` covers what that component's PHP imports or extends. A split installed alone must load every class it ships: `gpio/digital`'s transports extend `NutsAndBolts\CarrierTransport`, so it requires `gpio/nuts-and-bolts`. `gpio/nuts-and-bolts` carries `CarrierTransport`, `BusQueue`, `OffloadsBusJobs`, `TransportCall`, `Bytes` and the byte helpers, and imports only `Voyager\Contracts`, so it requires no IOPools component. `gpio/digital` and `gpio/uart` extend `Voyager\IOPools\Resources\WakeSource` for their watches, so they require `venusian-voyager/io-pools`.

# Rule: hardware is suggest only, and it is the adapters

No component `require`s a hardware package — always `suggest`, so a posix-only box never drags `ext-ftdi` and an ext-free box (CI, most dev machines) still installs. A protocol component suggests the **adapter**, `microscrap/scrapyard-linux` and/or `microscrap/scrapyard-usb`, not the low-level `microscrap/*` bindings those adapters sit on.

# Planned: an RPC adapter for Arduino's Q boards

A third adapter, `microscrap/scrapyard-rpc` (its directory already exists beside the other two), is owed for Arduino's dual-processor boards, the UNO Q and the VENTUNO Q. Their header pins belong to the MCU, not to the Linux side PHP runs on, so neither ext-posi nor ext-ftdi reaches them.[^router]

- **Path to the pins:** Linux reaches the MCU only through the `arduino-router` daemon. It speaks MessagePack-RPC (request/response and fire-and-forget notify) on `unix:///var/run/arduino-router.sock`, with `tcp://host:port` for development.[^router] The MCU sketch exposes functions with `Bridge.provide()`, and Linux code calls them with `call()` / `notify()`.[^router]
- **What the adapter needs:** a MessagePack-RPC client over that socket, whose stream joins the loop the way a UART port's does. It also needs a companion sketch on the MCU that provides digital, I2C, SPI, UART and PWM operations, and drivers registered as `rpc` on the protocol managers.
- **Latency:** a Linux → MCU → Linux call costs milliseconds, not microseconds, so bulk transfers and edge watching belong in the sketch, which mails results back over `notify`.

# Rule: each component declares its own provider

`extra.venusian.providers` on the component's own manifest, not just the umbrella's. No aliases: scrapyard ships no MagicAliases, and managers are container keys.

| Package | Provider | Container key |
|---|---|---|
| `gpio/digital` | `GeneralPurposeIO\Digital\DigitalIOServiceProvider` | `gpio.digital` |
| `gpio/i2c` | `GeneralPurposeIO\I2C\I2CServiceProvider` | `gpio.i2c` |
| `gpio/spi` | `GeneralPurposeIO\SPI\SPIServiceProvider` | `gpio.spi` |
| `gpio/uart` | `GeneralPurposeIO\UART\UARTServiceProvider` | `gpio.uart` |
| `gpio/pwm` | `GeneralPurposeIO\PWM\PWMServiceProvider` | `gpio.pwm` |
| `gpio/integrated-circuits` | `GeneralPurposeIO\IntegratedCircuits\IntegratedCircuitsServiceProvider` | `circuit` |
| `gpio/contracts`, `gpio/nuts-and-bolts` | none | — |

Aggregate `Core\Providers\ScrapyardIOServiceProvider` (root manifest only) merges `config/gpio.php`, publishes it as `gpio-config`, and lists the five protocol providers plus the integrated-circuits one, and binds + registers the two dev commands.

# Requires as they stand, per package

| Package | `require` (besides `php`) | `suggest` |
|---|---|---|
| `gpio/contracts` | `venusian-voyager/contracts` | — |
| `gpio/nuts-and-bolts` | `gpio/contracts`, `venusian-voyager/contracts` | — |
| `gpio/integrated-circuits` | `gpio/contracts`, `venusian-voyager/{contracts,nuts-and-bolts}` | — |
| `gpio/digital` | `gpio/{contracts,nuts-and-bolts}`, `venusian-voyager/{collections,contracts,io-pools,nuts-and-bolts}` | `microscrap/scrapyard-linux`, `microscrap/scrapyard-usb` |
| `gpio/uart` | the digital set | `microscrap/scrapyard-linux`, `microscrap/scrapyard-usb` |
| `gpio/i2c` | `gpio/{contracts,nuts-and-bolts}`, `venusian-voyager/{collections,contracts,nuts-and-bolts}` | `microscrap/scrapyard-linux`, `microscrap/scrapyard-usb` |
| `gpio/spi` | `gpio/{contracts,nuts-and-bolts}`, `venusian-voyager/{collections,contracts,nuts-and-bolts}` | `microscrap/scrapyard-linux`, `microscrap/scrapyard-usb` |
| `gpio/pwm` | `gpio/{contracts,nuts-and-bolts}`, `venusian-voyager/{collections,contracts,nuts-and-bolts}` | `microscrap/scrapyard-linux` |

No protocol requires another protocol. The adapters require every `gpio/*` protocol they drive and so sit above all of them.

Root umbrella `composer.json`: `require` = `venusian-voyager/{io-pools,contracts,collections,nuts-and-bolts,console,process,workflows} ^0.10.0`, `laravel/prompts ^0.3.0`, `symfony/{filesystem,process} ^8.0.0`, `composer-runtime-api ^2.2` (the last group for Core's dev commands; no split carries them), `replace` = the eight `gpio/*` at `self.version`, `require-dev` = `pestphp/pest ^4`, `mockery/mockery ^1.6` (`Prompt::fake()`), `venusian-voyager/{config,vessel}`, `suggest` = the `microscrap/*` bindings. No hardware package in `require-dev`: the suite runs on fakes, so it installs from Packagist on any machine.

# Dependency direction

Contracts imports `Voyager\Contracts` only. Components import Contracts, NutsAndBolts, `Voyager\Contracts` and `Voyager\NutsAndBolts` (`Collection`, `Manager`, `ServiceProvider`). Nothing below Core imports Core. No split imports `venusian/framework`.

# Rule: runtime still needs a Venusian application

The splits require components, not the framework, but providers call the `config()` / `app()` helpers and the catalog reads `config('circuits.<slug>')`, so any `gpio/*` package still runs inside a booted Venusian application.

# Related

* [overview.md](overview.md)
* [integrated-circuits.md](integrated-circuits.md)

[^router]: arduino-router-bridge-py (Arduino's MessagePack-RPC client for the router)
