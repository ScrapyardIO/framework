# scrapyard-io/framework Update Log

## 2026-09-16
* **Revision**: [dock-resource.md](/dock-resource.md) — resource ported onto the connection layer: `DigitalInputTransport` is the `EdgeSource`, `UARTTransport` the `ByteSource`; `DigitalIOConnectionDriver::input()` binds the device so edge mail is `gpio.edge.<device>.<offset>` (the posix/MPSSE offset collision closed). Two verbs added, `every(name, work, ticks)` → `Recurrence` and `stream(name, write, bytes, chunk)` → `Presumption` with progress; tick order is sources, deferred, recurrences, streams; snapshot rule covers all three. `registerDockResource()` re-enabled in the aggregate provider's `boot()`.
* **Addition**: `GPIOException::{recurrenceInFlight, invalidCadence, streamInFlight, invalidChunk}`; `Contracts\Core\Recurrence`.
* **Proof**: surface-dev boot lists dock resources `http, gpio, os`; cadence-2 recurrence ran twice in four pumps; 10-byte stream in 4-byte chunks settled with 10. Dry suite: `tests/Core`, `tests/Contracts`, `tests/Connections`, `tests/Providers` — 144 passed.
* **Removal**: `tests/Digital/DigitalInputPinTest.php`, `tests/UART/UARTBusByteSourceTest.php` (classes gone); replaced by `tests/Connections/TransportsAreDockSourcesTest.php`.

## 2026-09-15
* **Correction**: [packaging.md](/packaging.md) — `gpio/contracts` actually imports `Voyager\Contracts\IOPools\{IOResourceDriver,Occurrence,Completion}` and the concrete `Voyager\IOPools\Presumption`, so it requires `venusian-voyager/contracts` and `venusian-voyager/io-pools`, not `php` alone; added the rule that every `gpio/*` package still needs a booted Venusian application at runtime for `config()`/`app()`. `AGENTS.md`'s dependency-direction line corrected to match.
* **Addition**: [known-gaps.md](/known-gaps.md) — `gpio/contracts`' concrete `Presumption` dependency (no upstream interface yet); the Pi proof only covers `defer()` over I2C, not posix `watch`/`receive` or MPSSE/FTDI polling; no hardware-tagged Pest group exists.
* **Correction**: [overview.md](/overview.md) — recomputed file counts (168 total, Contracts 48, Core 4, was 160/41/3); dropped the wave-numbered port-plan table for a plain port-lineage note; stripped task/wave journal phrasing bundle-wide.
* **Correction**: [dock-resource.md](/dock-resource.md) — noted the MPSSE sample is one USB transaction bounded by the FTDI latency timer, not a zero-wait poll; noted `gpio.edge.<n>` names collide between a posix line offset and an MPSSE pin index of the same number.
* **Fix**: `GPIOProtocolManager::protocol()` threw on an undefined `GPIOException::invalidProperty()` for an unknown protocol name; added `GPIOException::unknownProtocol()`.
* **Fix**: `UsbDigitalIODriver::pollEdges()` read the pin twice on a cold poll; now primes the cache with one read and returns no events, or diffs the cache against one fresh read.
* **Proof**: `examples/pi-dock-proof.php` ran on the Pi 5 (`fnk0107`) against the FNK0107 fan board at 0x21 on i2c-1. One `tick()` ran five `defer()` transfers: brand `FREENOVE`, version `20251015_V1.0`, temp 40 C, LED mode static, all LEDs blue; all five `ok=yes`; a second tick returned the LEDs to rainbow. `vendor/bin/pest` on the Pi: 43 passed.
* **Creation**: [dock-resource.md](/dock-resource.md) — the gpio resource landed.

## 2026-09-14
* **Creation**: bundle seeded for 0.8.0 — overview, gpio-protocols, circuits, packaging, known-gaps. Port from `scrapyard-io/gpio-framework` 0.7.0 per `docs/superpowers/specs/2026-09-14-gpio-framework-0-8-port-design.md`.
