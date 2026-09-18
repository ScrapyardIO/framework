---
type: Concept
title: Known gaps
description: A suite that will not install standalone from Packagist, CI without hardware exts, About switched off, and other rough edges left open.
tags: [known-gaps, install, ci, about]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-18T21:40:00Z" }
sources:
  - id: root-composer
    resource: composer.json
    title: root composer.json
  - id: gpio-config
    resource: config/gpio.php
    title: gpio.php
  - id: provider
    resource: src/GeneralPurposeIO/Core/Providers/ScrapyardIOServiceProvider.php
    title: ScrapyardIOServiceProvider
  - id: ci
    resource: .github/workflows/tests.yml
    title: tests.yml
  - id: proof
    resource: examples/pi-dock-proof.php
    title: pi-dock-proof.php
---

# The suite does not install standalone

Root `require-dev` asks for `microscrap/{ftdi,gpio,i2c,mpsse,posix,spi,uart}: ^0.8.0`. On Packagist those seven are still at 0.7.0, so `composer install` here cannot resolve and there is no `vendor/`. The two adapters above them, `microscrap/scrapyard-linux` and `microscrap/scrapyard-usb`, **are** published at 0.8.0 — but each requires `microscrap/gpio: ^0.8.0` and friends, so switching `require-dev` to the adapters hits the same wall. All eleven exist locally at 0.8.0; the fix is tagging and publishing the seven bindings, not editing a manifest.

It does install from the working tree: temporary `path` repositories for `venusian/framework` and `../../microscrap/*`, plus `--ignore-platform-req=ext-posi --ignore-platform-req=ext-ftdi`, resolve and run the whole suite. That is a scaffold, not a committed manifest — no `repositories` key belongs in this repo.

# About is switched off

`registerAboutSection()` is written but its call in `boot()` is commented out, so no Workshop **GPIO** section ships. Its `microscrapIsInstalled()` probes also still name the low-level bindings (`microscrap/gpio`, `microscrap/mpsse`, `microscrap/posix`, …) rather than the `scrapyard-linux` / `scrapyard-usb` adapters the protocol components now suggest — so even re-enabled it would under-report on an adapter-only install.

# Protocol config lost its enabled flags

`config/gpio.php` is now `protocols.<name>.default` plus `io_pools`. The per-protocol `enabled` booleans and `adapters` maps of 0.7 are gone; every protocol is on, and `'none'` is the default driver for all six entries. Nothing reads `config('gpio.protocols.*.enabled')` any more — don't write code that expects it.

# Hardware drivers moved out; their gaps went with them

`PosixDigitalIODriver`, `UsbDigitalIODriver`, `UsbUARTDriver` and `FtdiUARTFactory` no longer live here — `microscrap/scrapyard-linux` and `microscrap/scrapyard-usb` own them. Two gaps recorded against them in 0.7/early-0.8 are now that repo's, not this one's: the `GPIOLineRequest|int $pin` union that each driver understands only half of, and USB/FTDI `pollBytes` being a time-boxed drain (~2 ms worst case) rather than a true non-blocking poll. This framework keeps only transports and connection drivers.

# NutsAndBolts\CarrierTransport cannot load

`src/GeneralPurposeIO/NutsAndBolts/CarrierTransport.php` is broken two ways and has no callers. Its `namespace` is `GeneralPurposeIO\Support`, but the file sits under `NutsAndBolts/`, whose PSR-4 root is `GeneralPurposeIO\NutsAndBolts\` — so nothing can autoload it. It also `implements GeneralPurposeIO\Contracts\NutsAndBolts\CarrierTransport`, an interface that does not exist (`Contracts\NutsAndBolts` holds `GPIOException`, `GPIOTransport`, `Splices16Bits`). Arrived in the align commit that published the 0.8 splits; left alone because the intended contract shape is a design call, not a typo.

# Mac composer update needs ignore-platform-req flags

`ext-posi` reports `0.5.0` and `ext-ftdi` reports `0.7.0` on this Mac; the manifests want `^0.8.0` for both. Any resolve on Mac needs `--ignore-platform-req=ext-posi --ignore-platform-req=ext-ftdi`. Not a framework bug — the fix is rebuilding both exts at 0.8.0.

# CI cannot resolve venusian/framework or microscrap/* from a real VCS

`.github/workflows/tests.yml` rewrites `composer.json`'s `repositories` to VCS entries before `composer install`, because path repositories only exist on this machine. Whether those remotes are public was never confirmed. Ubuntu runners carry neither `ext-posi` nor `ext-ftdi`, so hardware-tagged tests would skip there regardless. The job stays `continue-on-error: true`; a red or skipped run there is not evidence about the framework.

# Pi proof covers defer() over I2C only

`examples/pi-dock-proof.php` exercises `GPIOResourceDriver::defer()` against the FNK0107 fan board on i2c-1 — five deferred transfers, one `tick()`. Still not run on hardware: posix `watch` (edge polling), posix `receive` (UART byte polling), MPSSE digital polling, FTDI UART polling. Fakes cover all four; none has a hardware run.

# No hardware-tagged Pest group

Nothing in `tests/` is tagged for real hardware — the suite runs entirely against `tests/Support/Fakes` on any machine. The Pi proof is a standalone script, not a Pest group, so there is no `@group hardware` to skip on CI and run on the Pi.

# Related

* [overview.md](overview.md)
* [packaging.md](packaging.md)
* [integrated-circuits.md](integrated-circuits.md)
