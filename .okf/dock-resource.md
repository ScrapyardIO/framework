---
type: Concept
title: The gpio dock resource
description: >-
  GPIOResourceDriver, the framework's gpio IOPool resource: watch edges,
  receive bytes, defer work, one never-waiting tick, four mail species.
tags: [gpio, io-pools, dock, tick, mail]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-15T04:00:00Z" }
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
  - id: mail
    resource: src/GeneralPurposeIO/Contracts/Core/Mail
    title: DigitalEdgeOccurrence, UARTBytesOccurrence, TransferCompletion, SourceFaultOccurrence
  - id: digital-pin
    resource: src/GeneralPurposeIO/Digital/DigitalInputPin.php
    title: DigitalInputPin
  - id: digital-drivers
    resource: src/GeneralPurposeIO/Digital/Drivers
    title: PosixDigitalIODriver, UsbDigitalIODriver
  - id: uart-bus
    resource: src/GeneralPurposeIO/UART/Bus/UARTBus.php
    title: UARTBus
  - id: uart-drivers
    resource: src/GeneralPurposeIO/UART/Drivers
    title: PosixUARTDriver, UsbUARTDriver
  - id: provider
    resource: src/GeneralPurposeIO/Core/Providers/ScrapyardIOServiceProvider.php
    title: ScrapyardIOServiceProvider::boot()
  - id: gpio-exception
    resource: src/GeneralPurposeIO/Contracts/Common/GPIOException.php
    title: GPIOException
  - id: dock
    resource: venusian/framework:src/Voyager/IOPools/IOPoolDock.php
    title: IOPoolDock
  - id: dock-provider
    resource: venusian/framework:src/Voyager/IOPools/IOPoolsServiceProvider.php
    title: IOPoolsServiceProvider
  - id: tests-driver
    resource: tests/Core/GPIOResourceDriverTest.php
    title: GPIOResourceDriver tests
  - id: tests-boot
    resource: tests/Core/ProviderBootTest.php
    title: dock registration test
---

# One law

Tick never waits. Dock hands out edges and bytes, does not race them.
Every poll on `gpio`'s tick returns fast or returns empty — no sleep, no
blocking read. Sub-tick timing (debounce, baud framing, exact edge
spacing) is not this resource's job. That belongs to the peripheral, or
to the IC reading the mail.[^resource]

# Verbs

| Verb | Signature | Does |
|---|---|---|
| `watch` | `watch(EdgeSource $source, bool $rising = true, bool $falling = false): static` | add a pin to the poll set, keyed by `spl_object_id` |
| `unwatch` | `unwatch(EdgeSource $source): static` | drop a pin from the poll set |
| `receive` | `receive(ByteSource $source, int $max_bytes = 4096): static` | add a port to the poll set with a per-tick byte cap |
| `stopReceiving` | `stopReceiving(ByteSource $source): static` | drop a port from the poll set |
| `defer` | `defer(string $name, Closure $work, ?Closure $envelope = null): Presumption` | queue `$work` for the next tick; one name in flight at a time, else throws `GPIOException::transferInFlight` |
| `inFlight` | `inFlight(string $name): ?Presumption` | the live Presumption for a name, or null |

All six are opt-in per IC — nothing watches or defers on its own.[^contract]
`watch`/`unwatch` take an `EdgeSource`[^edge-source]; `receive`/`stopReceiving`
take a `ByteSource`[^byte-source].

# Mail

Four species, all `Voyager\Contracts\IOPools\QueuedIO`:

| Class | Name | Payload |
|---|---|---|
| `DigitalEdgeOccurrence` | `gpio.edge.<offset>` | `offset`, `edge` (`SignalEdge`), `timestamp_ns` |
| `UARTBytesOccurrence` | `gpio.uart.<path>` | `path`, `bytes` |
| `TransferCompletion` | `gpio.transfer.<transfer>` | `transfer`, `result`, `?error`; `ok()` is "did not throw" |
| `SourceFaultOccurrence` | `gpio.fault.<source>` | `source` (e.g. `edge.17`, `uart./dev/ttyAMA0`), `error` |

`<offset>` is the posix libgpiod line offset for a posix `DigitalInputPin`, or the raw MPSSE pin index for a USB one — both come from the same `DigitalInputPin::offset()`. A posix line and an MPSSE pin sharing the same number produce the same mail name, `gpio.edge.<n>`, in a sketch that mixes both carriers.

Same-named mail repeats within one tick — the dock's bag keeps every
entry, in order. `LiveApplication::events()` (Surface) re-keys the
drained bag by `name` via `keyBy`, so that path keeps only the last
entry per name. To see every edge from a burst, drain the bag directly
instead of going through `events()`.[^mail]

A watched pin or received port that throws during poll becomes a
`SourceFaultOccurrence` instead — `gpio.fault.edge.<offset>` or
`gpio.fault.uart.<path>`, carrying the caught `$error`. The source
stays registered after a fault; nothing in the resource unwatches it.
Dropping a faulting source is the IC's call, made from wherever it
reads `gpio.fault.*` mail.[^resource]

# Tick order

One `tick()`:

1. Every watched `EdgeSource`, in registration order — `pollEdges($rising, $falling)`, push one `DigitalEdgeOccurrence` per returned edge, or one `SourceFaultOccurrence` on a throw.
2. Every received `ByteSource`, in registration order — `pollBytes($max_bytes)`, push one `UARTBytesOccurrence` when non-empty, or one `SourceFaultOccurrence` on a throw.
3. Deferred work, FIFO, capped by `gpio.io_pools.defer_per_tick` (`null` = drain the whole queue that tick; an int takes the first N, the rest wait).

Only work queued **before the tick started** runs in that tick. A
`Presumption::onSuccess`/`onFail` hook that calls `defer()` again lands
its new entry at the end of `$this->deferred`, after the tick's
`array_splice` already took its slice — so a re-defer always runs on
the *next* tick, never the same one.[^resource][^tests-driver]

Hooks run inline, inside `tick()`, and are not contained — a throwing
hook throws out of `tick()`. The deferred closure itself must not
`defer()` its own name: `in_flight[$name]` is only cleared *after* the
closure returns, so calling `defer($name, ...)` from inside that same
closure throws `transferInFlight`. Reschedule from the `onSuccess`/
`onFail` hook instead, where `in_flight` has already been cleared.[^resource][^tests-driver]

A throwing envelope, or one that returns something that is not
`QueuedIO`, falls back to pushing the raw `TransferCompletion` — the
Presumption still settles on the real completion either way.[^resource]

`defer_per_tick` (constructor's `$defer_per_tick`) must be `null` or
`>= 1`; anything else throws `GPIOException::invalidDeferBudget` at
construction, before the resource is usable.[^resource][^gpio-exception]

# Registration

`ScrapyardIOServiceProvider::boot()` registers the resource as `gpio`
on the dock, when `config('gpio.io_pools.enabled')` is true and
`app()->bound('io-pool')`. The configured `gpio.io_pools.defer_per_tick`
passes straight through: `null` stays `null`, anything else casts to
`int`.[^provider]

```php
$dock->resource('gpio', new GPIOResourceDriver($dock, $cap));
```

Proven by booting a real `Application` with `IOPoolsServiceProvider`
registered ahead of the aggregate, then resolving `io-pool` and calling
its `gpio()` accessor.[^tests-boot]

In a real app's provider list this runs after `http`/`async` (both
registered from `IOPoolsServiceProvider::boot()`'s `bootResources()`)
and before Surface's lazily-built `os` resource (built and registered
from `SurfaceServiceProvider::boot()`, on first resolve of
`OSLevelResourceDriver`). Nothing in `IOPoolDock` depends on that
order — `resource()` just stores by name in a `Collection` — so this
is what a normal provider list produces, not something the dock
enforces. Document it, don't rely on it.[^dock][^dock-provider]

# Sources

`DigitalInputPin` is an `EdgeSource`; `pollEdges()` delegates to its
`DigitalIODriver`.[^digital-pin]

- `PosixDigitalIODriver::pollEdges()` calls
  `gpiod_line_request_wait_edge_events($pin, 0)` — a zero-timeout wait,
  truly non-blocking — then drains libgpiod's edge-event buffer for
  whatever already queued.
- `UsbDigitalIODriver::pollEdges()` (MPSSE) has no event buffer to
  drain: on a cold pin (no cached value yet) it primes the cache with
  one `read()` and returns no events; from then on each poll takes one
  fresh `read()` and diffs it against the previous cached value. Either
  way that's one MPSSE sample per poll, not zero — and that one sample
  is one USB transaction, bounded by the FTDI device's latency timer,
  not a zero-wait poll like the posix path.[^digital-drivers]

`UARTBus` is a `ByteSource`; `pollBytes()` delegates to its
`UARTDriver`.[^uart-bus]

- `PosixUARTDriver::pollBytes()` calls `posix_ppoll($fd, 0)` — zero
  timeout, truly non-blocking — then reads only if ppoll says
  something is ready.
- `UsbUARTDriver::pollBytes()` (FTDI) has no such primitive: it
  time-boxes a 1-byte-at-a-time drain against
  `$poll_budget_ns` (1 ms), plus at most one USB bulk transfer per
  poll, bounded by the 1 ms latency timer `FtdiUARTFactory` sets on
  the device. Worst case is roughly 2 ms per poll — not truly
  non-blocking — because libftdi's `ftdi_read_data` would otherwise
  loop until `$size` bytes arrive, and `FTDIContext` exposes no
  buffered-byte count to check first.[^uart-drivers]

# IC usage

A defer wraps blocking work so the tick never carries it directly (from
`dept-of-scrapyard-robotics`, not this repo):

```php
public function measureLater(): Presumption
{
    return $this->gpio->defer('aht20.measure', fn () => $this->measure());
}
```

[^resource]: GPIOResourceDriver
[^contract]: GPIOResourceDriver contract
[^mail]: DigitalEdgeOccurrence, UARTBytesOccurrence, TransferCompletion, SourceFaultOccurrence
[^digital-pin]: DigitalInputPin
[^digital-drivers]: PosixDigitalIODriver, UsbDigitalIODriver
[^uart-bus]: UARTBus
[^uart-drivers]: PosixUARTDriver, UsbUARTDriver
[^provider]: ScrapyardIOServiceProvider::boot()
[^gpio-exception]: GPIOException
[^dock]: IOPoolDock
[^dock-provider]: IOPoolsServiceProvider
[^tests-driver]: GPIOResourceDriver tests
[^tests-boot]: dock registration test
[^edge-source]: EdgeSource
[^byte-source]: ByteSource
