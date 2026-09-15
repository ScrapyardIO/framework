---
type: Concept
title: Known gaps
description: Analog scaffold, PWM disabled, CI without hardware exts, deliberate 0.8 moves, and other rough edges left open.
tags: [known-gaps, analog, pwm, ci]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-15T04:00:00Z" }
sources:
  - id: analog-provider
    resource: src/GeneralPurposeIO/Analog/AnalogServiceProvider.php
    title: AnalogServiceProvider
  - id: gpio-config
    resource: config/gpio.php
    title: gpio.php
  - id: digital-drivers
    resource: src/GeneralPurposeIO/Digital/Drivers
    title: PosixDigitalIODriver, UsbDigitalIODriver
  - id: contracts-digital
    resource: src/GeneralPurposeIO/Contracts/Digital/DigitalEdgeEvent.php
    title: DigitalEdgeEvent
  - id: contracts-circuit-transport
    resource: src/GeneralPurposeIO/Contracts/Circuits/CircuitTransport.php
    title: CircuitTransport
  - id: ci
    resource: .github/workflows/tests.yml
    title: tests.yml
  - id: uart-drivers
    resource: src/GeneralPurposeIO/UART/Drivers/UsbUARTDriver.php
    title: UsbUARTDriver
---

# Mac composer update needs ignore-platform-req flags

`ext-posi` binary reports `0.5.0`, `ext-ftdi` reports `0.7.0` on this Mac. Every manifest here requires `^0.8.0` for both. `composer update`/`install` on Mac needs `--ignore-platform-req=ext-posi --ignore-platform-req=ext-ftdi` or resolve fails. Not a framework bug — fix is rebuilding both exts at 0.8.0, not touching a manifest.

# AnalogServiceProvider::provides() lists gpio.analog-in twice

`AnalogServiceProvider::provides()` returns `['gpio.analog-in', 'gpio.analog-in']` — duplicate entry, carried over from 0.7 unchanged. Harmless (deferred-provider `provides()` list dedupes downstream) but left as is; a packaging/docs pass doesn't touch component bugs like this one.

# UsbDigitalIODriver / PosixDigitalIODriver share a signature, not a shape

Both `write()`, `read()`, `listen()` type-hint `GPIOLineRequest|int $pin` on `DigitalIODriver`. `PosixDigitalIODriver` understands only a real `GPIOLineRequest` (reads `$pin->offsets[0]`, calls `gpiod_line_request_*`). `UsbDigitalIODriver` understands only a bare MPSSE pin index (calls `mpsse_pin_*($this->context, $pin)`, no `offsets` access). Wrong shape to either driver: not guarded, fails at the microscrap call, not at the PHP type boundary — union type accepts both.

# Analog is scaffold, never implemented

`config('gpio.protocols.analog-in.enabled')` is `false`; `analog-out` has no adapters at all (`'adapters' => []`). `GeneralPurposeIO\Analog` exists as namespace + provider, not a working transport. Don't read "ships" as "works" for Analog.

# PWM ships disabled

`config('gpio.protocols.pwm.enabled')` is `false` even though `NativePWMAdapter` is implemented. Deliberate — flip config flag once someone verifies against real `/sys/class/pwm` hardware, not before.

# DigitalEdgeEvent and CircuitTransport moved into Contracts (0.8, deliberate)

0.7 had `DigitalEdgeEvent` under `Digital` component, `CircuitTransport` under `Circuits\Enums`. 0.8 moved both into `Contracts` (`Contracts\Digital\DigitalEdgeEvent`, `Contracts\Circuits\CircuitTransport`) so contract package fully mirrors what driver interfaces reference. Decision, not drift — don't move them back.

# CI cannot resolve venusian/framework or microscrap/* from a real VCS yet

`.github/workflows/tests.yml` rewrites `composer.json`'s `repositories` to VCS entries (`https://github.com/VenusianPHP/framework`, `https://github.com/microscrap/<name>`) before `composer install` — path repositories only exist on this machine. Whether those remotes are public: not confirmed as of 2026-09-15. `gh` installed but not authenticated here (`gh auth status` → not logged in), so `gh repo view` couldn't check visibility for either. Job stays `continue-on-error: true` until someone confirms remotes public and reachable; a red or skipped run here doesn't mean the framework's broken. Ubuntu runners also carry neither `ext-posi` nor `ext-ftdi`, so hardware-tagged tests skip there regardless — a skipped test is not evidence, real coverage is the Pi over `fnk`.

# USB/FTDI UART pollBytes is not truly non-blocking

Posix edge polls and posix UART `pollBytes` are zero-timeout ppolls —
truly non-blocking. USB/FTDI UART `pollBytes` isn't: it's a time-boxed
1-byte drain bounded by `UsbUARTDriver::$poll_budget_ns` (1 ms), plus at
most one bulk transfer under the 1 ms latency timer `FtdiUARTFactory`
sets on the device — worst case roughly 2 ms per poll. libftdi's
`ftdi_read_data` would otherwise loop until `$size` bytes arrive, and
`FTDIContext` exposes no buffered-byte count to check first, so there's
no zero-wait primitive to call instead.

USB UART pollBytes not yet hardware-verified.

# gpio/contracts depends on a concrete Voyager class, not an interface

`GPIOResourceDriver::defer()` (`Contracts\Core\GPIOResourceDriver`) returns `Voyager\IOPools\Presumption` — a concrete class, not a contract. `venusian/framework` ships no `Presumption` interface, so `gpio/contracts` requires `venusian-voyager/io-pools` (the concrete IOPools package) alongside `venusian-voyager/contracts`, which every other split component also needs. An upstream `Voyager\Contracts\IOPools\Presumption` interface would let `gpio/contracts` depend on contracts alone.

# Pi proof covers defer() over I2C only

`examples/pi-dock-proof.php` exercises `GPIOResourceDriver::defer()` against `PosixI2CAdapter` (FNK0107 fan board on i2c-1) — five deferred transfers, one `tick()`. Not yet run on hardware: posix `watch` (`PosixDigitalIODriver::pollEdges`), posix `receive` (`PosixUARTDriver::pollBytes`), MPSSE digital polling (`UsbDigitalIODriver::pollEdges`), and FTDI UART polling (`UsbUARTDriver::pollBytes`). `vendor/bin/pest` covers all four in fakes; none of the four has a hardware run yet.

# No hardware-tagged Pest group

Nothing in `tests/` is tagged for real hardware — the suite runs entirely against fakes (`tests/Support/Fakes`) on any machine. The Pi 5 proof is `examples/pi-dock-proof.php`, a standalone script, not a Pest group; there's no `@group hardware` (or equivalent) to skip on CI and run on the Pi.

# Related

* [overview.md](overview.md)
* [packaging.md](packaging.md)
