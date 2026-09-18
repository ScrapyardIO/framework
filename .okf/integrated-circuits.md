---
type: Concept
title: Integrated circuits
description: The chip vocabulary and the catalog — Sensor / Actuator / DisplayPanel kinds, ReadWriter and DataCommander, Bootable and DataRegister, and CircuitRegistry::conjure() building a wired chip from its circuits config.
tags: [integrated-circuits, contracts, taxonomy, catalog, registry, conjure, config]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-18T21:40:00Z" }
sources:
  - id: ic
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/IntegratedCircuit.php
    title: IntegratedCircuit contract
  - id: sensor
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/Sensor.php
    title: Sensor
  - id: actuator
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/Actuator.php
    title: Actuator
  - id: readwriter
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/ReadWriter.php
    title: ReadWriter
  - id: commander
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/DataCommander.php
    title: DataCommander
  - id: boot
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/BootSequence.php
    title: BootSequence
  - id: scaffolding
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/BootScaffolding.php
    title: BootScaffolding
  - id: bootable
    resource: src/GeneralPurposeIO/IntegratedCircuits/Bootable.php
    title: Bootable
  - id: register
    resource: src/GeneralPurposeIO/IntegratedCircuits/DataRegister.php
    title: DataRegister
  - id: exception
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/CircuitException.php
    title: CircuitException
  - id: manifest
    resource: src/GeneralPurposeIO/IntegratedCircuits/composer.json
    title: gpio/integrated-circuits composer.json
  - id: registry
    resource: src/GeneralPurposeIO/IntegratedCircuits/CircuitRegistry.php
    title: CircuitRegistry
  - id: alias
    resource: src/GeneralPurposeIO/Core/MagicAliases/Circuit.php
    title: Circuit alias
  - id: provider
    resource: src/GeneralPurposeIO/IntegratedCircuits/IntegratedCircuitsServiceProvider.php
    title: IntegratedCircuitsServiceProvider
  - id: config
    resource: ../../dept-of-scrapyard-robotics/st77xx/config/st7789.php
    title: a chip package's circuits config
---

# Role

The vocabulary a chip driver speaks, and the catalog that builds one.
Chip drivers live in `dept-of-scrapyard-robotics/*` and implement these; the
framework catalogs them without knowing any one of them.

# Kinds

Empty marker interfaces, each `extends IntegratedCircuit` (itself empty):[^ic][^sensor][^actuator]

| Kind | Who |
|---|---|
| `Sensor` | ADXL34x, VL6180X |
| `Actuator` | motors, relays |
| `DisplayPanel` | SSD1306, ST77xx — see [display-panels.md](display-panels.md) |

A chip picks the kind it is. Nothing dispatches on them yet; they exist so a
consumer can type-hint what it accepts.

# Transports

Two shapes a chip's own IO takes, independent of the kind:[^readwriter][^commander]

| Contract | Verbs |
|---|---|
| `ReadWriter` | `read($register, $length): array`, `write($register, array $data): int` |
| `DataCommander` | `data(array\|string $data = []): void`, `command($register, array $command_data = []): int` |

Register chips (accelerometer, ToF) are `ReadWriter`. Panels that separate a
command byte from a pixel stream are `DataCommander`.

# Boot

`BootSequence` declares `boot()` / `hasBooted()`. `BootScaffolding` — a trait,
in the contracts namespace — implements both over an abstract
`_boot(): void` the driver writes, and makes `boot()` idempotent.
`Bootable extends IntegratedCircuit implements BootSequence`, uses the trait,
and boots in the constructor when `$boot_now`.[^boot][^scaffolding][^bootable]

A consumer that needs a live chip checks `hasBooted()` — Surface's
`EmbeddedDisplayManager::attach()` refuses an unbooted panel.

# DataRegister

Readonly bit/byte breakout for register chips: `toBits()`, `toByte()`,
`fromByte()`, `none()`.[^register]

# The catalog

`Circuit` is the alias, `CircuitRegistry` the one binding behind it
(`circuit` on the container). A chip package catalogs what it ships from its
provider's `boot()`; nothing is built until something asks.[^registry][^alias]

```php
Circuit::addCircuit('st7789', ST7789::class);   // the chip package
Circuit::conjure('st7789');                     // an app — live, wired, booted
Circuit::conjure('st7789', 'left');             // a named config
```

`conjure()` reads `config('circuits.<slug>')`, takes `default_config` or the
config named, and calls the chip's own public static factory with that
config's keys as named arguments. The factory is the config's key unless the
entry sets `protocol`, so an app can keep `left` and `right` beside `spi`.
The registry knows no chip's constructor and no bus.

A key the factory does not declare is dropped; a factory parameter with no
default that the config does not carry is an error naming the parameter.
`addCircuit()` ignores a class that is not an `IntegratedCircuit` rather
than throwing — a catalog is built from many packages' `boot()`, and one bad
entry should not take the boot down.

# The wiring lives in the app

A chip package ships `config/<slug>.php` merged at `circuits.<slug>` and
publishes it to `config/circuits/<slug>.php`. The package's copy is a blank
with `driver => 'none'`; the app fills in the bench.[^config]

```php
// config/circuits/st7789.php
'default_config' => 'spi',
'configs' => ['spi' => [
    'driver' => 'usb', 'device' => 'ft232h', 'chip_select' => 0,
    'speed' => 10_000_000, 'width' => 320, 'height' => 240,
    'mad_ctrl' => ['pixel_direction_vertical' => true],
    'dc'  => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 1],
    'rst' => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 2],
]],
```

That entry is exactly the signature of `ST7789::spi()`. A chip's protocol
factory and its config entry are one shape, which is what lets the registry
stay ignorant of both.

Surface reaches the same door for a panel:
`EmbeddedDisplay::panel('st7789')` conjures the chip and attaches it as a
display in one call.

# Renamed from Circuits

0.7 called this component `Circuits`. 0.8 renamed it `IntegratedCircuits`
and turned the taxonomy inside out: 0.7's `Circuits\Types\{SensorIC,
Actuator, DisplayPanel}` were abstract classes a driver had to extend; 0.8's
are interfaces a driver implements, so a chip can be two things at once.
`AnalogIOCircuit`, `SecurityChip` and `StorageDevice` went away with Analog.

The catalog did not come across with the rename and was rebuilt afterwards.
It is not the 0.7 one: 0.7 had `PendingCircuit`, arbitrary profile keys in a
single `config/circuits.php`, and `#[IntegratedCircuit]` / `#[Pinout]`
attributes driving a `circuit:make-profile` scaffolder. 0.8 has one verb,
`conjure()`, over the per-chip config each chip package already ships and
publishes. The wiring was always going to live there; the catalog just reads
it.

# Related

* [display-panels.md](display-panels.md)
* [packaging.md](packaging.md)
* [known-gaps.md](known-gaps.md)

[^ic]: IntegratedCircuit contract
[^sensor]: Sensor
[^actuator]: Actuator
[^readwriter]: ReadWriter
[^commander]: DataCommander
[^boot]: BootSequence
[^scaffolding]: BootScaffolding
[^bootable]: Bootable
[^register]: DataRegister
[^registry]: CircuitRegistry
[^alias]: Circuit alias
