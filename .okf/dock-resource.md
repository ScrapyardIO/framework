---
type: Concept
title: The gpio dock resource
description: >-
  GPIOResourceDriver, the framework's gpio IOPool resource: watch edges,
  receive bytes, defer once, recur every N ticks, stream in chunks. One
  never-waiting tick, four mail species.
tags: [gpio, io-pools, dock, tick, mail]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-15T04:00:00Z" }
revised: { by: claude-fable-5-1/claude-code, at: "2026-09-16T00:00:00Z", note: "connection-layer sources; every and stream verbs; device in edge names" }
sources:
  - id: resource
    resource: src/GeneralPurposeIO/Core/IOPools/GPIOResourceDriver.php
    title: GPIOResourceDriver
  - id: contract
    resource: src/GeneralPurposeIO/Contracts/Core/GPIOResourceDriver.php
    title: GPIOResourceDriver contract
  - id: edge-source
    resource: src/GeneralPurposeIO/Contracts/Core/EdgeSource.php
    title: EdgeSource
  - id: byte-source
    resource: src/GeneralPurposeIO/Contracts/Core/ByteSource.php
    title: ByteSource
  - id: recurrence
    resource: src/GeneralPurposeIO/Contracts/Core/Recurrence.php
    title: Recurrence
  - id: mail
    resource: src/GeneralPurposeIO/Contracts/Core/Mail
    title: DigitalEdgeOccurrence, UARTBytesOccurrence, TransferCompletion, SourceFaultOccurrence
  - id: input-transport
    resource: src/GeneralPurposeIO/Digital/DigitalInputTransport.php
    title: DigitalInputTransport (EdgeSource)
  - id: uart-transport
    resource: src/GeneralPurposeIO/UART/UARTTransport.php
    title: UARTTransport (ByteSource)
  - id: digital-driver
    resource: src/GeneralPurposeIO/Digital/DigitalIOConnectionDriver.php
    title: DigitalIOConnectionDriver::input() binds the device
  - id: provider
    resource: src/GeneralPurposeIO/Core/Providers/ScrapyardIOServiceProvider.php
    title: ScrapyardIOServiceProvider::registerDockResource()
  - id: gpio-exception
    resource: src/GeneralPurposeIO/Contracts/NutsAndBolts/GPIOException.php
    title: GPIOException
  - id: tests-driver
    resource: tests/Core/GPIOResourceDriverTest.php
    title: resource tests (dry)
  - id: tests-sources
    resource: tests/Connections/TransportsAreDockSourcesTest.php
    title: transports are sources
  - id: tests-boot
    resource: tests/Core/ProviderBootTest.php
    title: dock registration test
---

# One law

Tick never waits. Every poll zero-timeout, every unit of scheduled work one
bounded step. Sub-tick timing (debounce, baud framing, WS2812 bit spacing)
not this resource's job — peripheral or IC.[^resource]

# Where the sources come from

Dock names no platform. `DigitalInputTransport` is an `EdgeSource`,
`UARTTransport` a `ByteSource`, at the base — posix and MPSSE transports
inherit it.[^input-transport][^uart-transport] `DigitalIOConnectionDriver::input()`
stamps the device on the pin it hands out (`boundTo`), so mail can name
the connection.[^digital-driver]

# Verbs

| Verb | Returns | Does |
|---|---|---|
| `watch(EdgeSource, rising=true, falling=false)` / `unwatch` | `static` | poll set, keyed `spl_object_id` |
| `receive(ByteSource, max_bytes=4096)` / `stopReceiving` | `static` | poll set with per-tick byte cap |
| `defer(name, Closure work, ?Closure envelope)` | `Presumption` | once, next tick; one name in flight or `transferInFlight` |
| `every(name, Closure work, ticks=1)` | `Recurrence` | repeat until `stop()`; one per name or `recurrenceInFlight`; `ticks < 1` → `invalidCadence` |
| `stream(name, Closure write, string bytes, int chunk)` | `Presumption` | one `chunk` per tick through `write`; one per name or `streamInFlight`; `chunk < 1` → `invalidChunk` |
| `inFlight` / `recurring` / `streaming` | handle or null | the live handle for a name |

All opt-in per IC. Nothing watches, recurs or streams on its own.[^contract]

Why two new verbs, not one per protocol: Digital-in and UART have something
to drain and are served by `watch` / `receive`. I2C, SPI, PWM and Digital-out
have nothing to drain; they need blocking work scheduled — once (`defer`),
on a cadence (`every`: gamepad poll, sensor sample, PWM fade, LED blink),
or in pieces (`stream`: panel frame over MPSSE, blob down a UART). The
writer closure is the seam, so the dock stays ignorant of SPI vs UART.

# Mail

Four species, all `Voyager\Contracts\IOPools\QueuedIO`:

| Class | Name | Payload |
|---|---|---|
| `DigitalEdgeOccurrence` | `gpio.edge.<device>.<offset>`, or `gpio.edge.<offset>` when unbound | `device`, `offset`, `edge` (`SignalEdge`), `timestamp_ns` |
| `UARTBytesOccurrence` | `gpio.uart.<path>` | `path`, `bytes` |
| `TransferCompletion` | `gpio.transfer.<name>` | `transfer`, `result`, `?error`; `ok()` = did not throw. From `defer`, each `every` run, and stream end (`result` = bytes sent) |
| `SourceFaultOccurrence` | `gpio.fault.<source>` | `source` = `edge.<device>.<offset>`, `uart.<path>`, `every.<name>`; `error` |

Same-named mail repeats within a tick; the bag keeps every entry in order.
Surface's `LiveApplication::events()` re-keys by `name` (`keyBy`), keeping
only the last per name — drain the bag directly to see a burst.[^mail]

A faulting pin, port or recurrence stays registered. Dropping it is the
IC's call, from wherever it reads `gpio.fault.*`.[^resource]

# Tick order

1. Watched pins, registration order — `pollEdges($rising, $falling)`.
2. Received ports, registration order — `pollBytes($max_bytes)`.
3. Deferred, FIFO, capped by `gpio.io_pools.defer_per_tick` (`null` = all).
4. Recurrences due this tick — `elapsed` counts from registration; runs when `elapsed % ticks === 0`. A run pushes `TransferCompletion` and calls `onEach`; a throw pushes `gpio.fault.every.<name>` and calls `onFail(Throwable)`. `stop()` honoured before the run and after it, including from inside the hook.
5. Streams — one chunk each; `Presumption::onProgress(sent, total)` after every chunk; last chunk completes in the same tick. Empty bytes complete on the first tick with `0`, no write. A throwing writer fails the stream with `result` = bytes sent so far.

Snapshot rule: only what was registered **before** the tick started runs in
that tick. A hook that defers, recurs or streams lands on the next tick.
Hooks run inline and are not contained.[^resource][^tests-driver]

# Registration

`ScrapyardIOServiceProvider::boot()` → `registerDockResource()`: resource
`gpio` on the dock when `config('gpio.io_pools.enabled')` and `io-pool` is
bound. `gpio.io_pools.defer_per_tick` passes through; `null` stays, else
`(int)`; `< 1` throws `invalidDeferBudget` at construction.[^provider][^tests-boot]

Proven live in surface-dev: dock resources `http, gpio, os`; a cadence-2
recurrence ran twice in four pumps; a 10-byte stream in 4-byte chunks
settled with `10`.

# IC usage (dept-of-scrapyard-robotics, not this repo)

```php
$gpio->defer('aht20.measure', fn () => $this->measure());               // once
$gpio->every('seesaw.poll', fn () => $this->poll(), ticks: 1);          // cadence
$gpio->stream('ssd1306.frame', fn (string $c) => $this->data($c), $frame, 1024); // pieces
```

[^resource]: GPIOResourceDriver
[^contract]: GPIOResourceDriver contract
[^mail]: DigitalEdgeOccurrence, UARTBytesOccurrence, TransferCompletion, SourceFaultOccurrence
[^input-transport]: DigitalInputTransport (EdgeSource)
[^uart-transport]: UARTTransport (ByteSource)
[^digital-driver]: DigitalIOConnectionDriver::input() binds the device
[^provider]: ScrapyardIOServiceProvider::registerDockResource()
[^tests-driver]: resource tests (dry)
[^tests-boot]: dock registration test
