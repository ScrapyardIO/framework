---
type: Concept
title: Circuits
description: CircuitRegistry catalog, PendingCircuit fluent builder, named profiles, taxonomy bases, and About IC inventory.
tags: [circuits, registry, ic, profile, fluent, about]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-15T02:07:33Z" }
sources:
  - id: registry
    resource: src/GeneralPurposeIO/Circuits/CircuitRegistry.php
    title: CircuitRegistry
  - id: pending
    resource: src/GeneralPurposeIO/Circuits/PendingCircuit.php
    title: PendingCircuit
  - id: make-profile
    resource: src/GeneralPurposeIO/Circuits/Console/CircuitMakeProfileCommand.php
    title: circuit:make-profile
  - id: provider
    resource: src/GeneralPurposeIO/Circuits/CircuitsServiceProvider.php
    title: CircuitsServiceProvider
  - id: about-rows
    resource: src/GeneralPurposeIO/Circuits/Support/CircuitCatalogAboutRows.php
    title: CircuitCatalogAboutRows
  - id: alias
    resource: src/GeneralPurposeIO/Core/MagicAliases/Circuit.php
    title: Circuit MagicAlias
  - id: config
    resource: config/circuits.php
    title: circuits.php
  - id: contract
    resource: src/GeneralPurposeIO/Contracts/Circuits/IntegratedCircuit.php
    title: IntegratedCircuit contract
  - id: ic-attr
    resource: src/GeneralPurposeIO/Contracts/Circuits/Attributes/IntegratedCircuit.php
    title: IntegratedCircuit attribute
  - id: pinout-attr
    resource: src/GeneralPurposeIO/Contracts/Circuits/Attributes/Pinout.php
    title: Pinout attribute
---

# Role

Circuits is IC catalog + build surface, owned by scrapyard-io/framework.

# Layers

| Layer | API | Meaning |
|-------|-----|---------|
| Catalog | `Circuit::addCircuit('st7789', ST7789::class)` | Package registers available IC **types** |
| Fluent | `Circuit::ic('st7789')->protocol('spi')->driver('usb')->device('ft232h')->…->make()` | On-demand instance |
| Profile | `Circuit::profile('front_panel')` | Config recipe → **live** instance (no `make()`) |

# How it works

1. `CircuitsServiceProvider` binds singleton `circuit` → `CircuitRegistry`, merges `config/circuits.php`, registers `circuit:make-profile`, publishes tag `gpio-circuits-config`.
2. IC packages call `Circuit::addCircuit($slug, $class)` from provider `boot()` — catalog only — and may `Circuit::registerProfileCommand($slug, 'pkg:make-profile')`.
3. Apps define **arbitrary** profile keys in `circuits.php` with `ic` + `protocol` + `params` (publish the Profile Definitions file first).
4. `Circuit::profile($name)` resolves `ic` from catalog, invokes `$class::$protocol(...$params)` via reflection named args.
5. `Circuit::ic($slug)` returns `PendingCircuit`; `make()` builds same way after fluent setters fill params.
6. `circuit:make-profile` lists installed catalog ICs; when a package registered a maker command it **delegates** (e.g. ST77xx → `st77xx:make-profile`). Scaffolding reads `#[IntegratedCircuit]` / `#[Pinout]` and **prompts** per channel for adapter (driver), device, roles (`chip_select`, `slave`, pin names → `{role}_pin`). Always includes `boot_now => true`.

**Removed:** `Circuit::driver($slug)` that treated catalog slug as config key.

# Fluent notes

- `driver($adapter)` — i2c/uart → `adapter`; spi → both `spi_adapter` and `digital_adapter`.
- `device($id)` — **always explicit** (e.g. `ft232h`, `ft2232h-a`, posix `1`); spi → both `spi_device` and `digital_device`.
- Helpers: `chipSelect`, `dc`→`dc_pin`, `rst`→`rst_pin`, `slave`, `args([...])`, `__call` camelCase→snake_case.

# Profile config shape

```php
'front_panel' => [
    'ic' => 'st7789',
    'protocol' => 'spi',
    'params' => [ /* named factory args */ ],
],
```

One catalog IC may appear in many profiles.

# Contracts

| Type | Purpose |
|------|---------|
| `Contracts\Circuits\IntegratedCircuit` | `close(): void` |
| `Contracts\Circuits\Attributes\IntegratedCircuit` | Protocol options (`'I2C'` or `['SPI','DigitalIO']`); `options()` for tooling |
| `Contracts\Circuits\Attributes\Pinout` | Per-option channel map: roles `driver`/`device`/`chip_select`/`slave`/pin names drive `circuit:make-profile` prompts |
| `Contracts\Circuits\CircuitTransport` | Maps channel labels → factory param prefixes (`spi_adapter`, `digital_device`, …). 0.8: moved into `Contracts\Circuits`, was `Circuits\Enums\CircuitTransport` in 0.7 |
| `Contracts\Circuits\CircuitException` | Registry / build errors |
| `Contracts\Circuits\CircuitRegistry` | Typing marker for the registry |

# Taxonomy (marker bases)

Empty abstracts extending `GeneralPurposeIO\Circuits\IntegratedCircuit`:

- `SensorIC`, `Actuator`, `DisplayPanel`, `AnalogIOCircuit`, `SecurityChip`, `StorageDevice`

# DataRegister

`GeneralPurposeIO\Circuits\DataRegister` — readonly bit/byte helpers for IC breakouts.

# About

`CircuitsServiceProvider` contributes Workshop `about` section **Integrated Circuits** via `CircuitCatalogAboutRows`:

- Left column: **catalog** slug from `Circuit::addCircuit` (never `config/circuits.php` profile keys).
- Right column: each `#[IntegratedCircuit]` option label, joined with `/` — e.g. `SPI+DigitalIO / SPI / DigitalIO` for `#[IntegratedCircuit(['SPI', 'DigitalIO'], 'SPI', 'DigitalIO')]`.
- Rows resolve at `about` run-time (after IC packages register). JSON emits the label list.

# Related

* [GPIO protocols](gpio-protocols.md)
* [packaging.md](packaging.md)
