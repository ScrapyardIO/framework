---
type: Concept
title: GPIO protocols
description: Protocol managers under gpio.protocols, the shared connection lifecycle, and what 0.9 ships (Digital, I2C, SPI).
tags: [gpio, protocols, i2c, digital, spi]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-09-25T05:15:00Z" }
sources:
  - id: provider
    resource: src/GeneralPurposeIO/Core/Providers/ScrapyardIOServiceProvider.php
    title: ScrapyardIOServiceProvider
  - id: config
    resource: config/gpio.php
    title: gpio.php
  - id: i2c-driver
    resource: src/GeneralPurposeIO/I2C/I2CConnectionDriver.php
    title: I2CConnectionDriver
  - id: digital-driver
    resource: src/GeneralPurposeIO/Digital/DigitalIOConnectionDriver.php
    title: DigitalIOConnectionDriver
  - id: spi-driver
    resource: src/GeneralPurposeIO/SPI/SPIConnectionDriver.php
    title: SPIConnectionDriver
---

# Role

0.9 ships three protocols: Digital, I2C and SPI. `ScrapyardIOServiceProvider` aggregates `DigitalIOServiceProvider` (`gpio.digital`), `I2CServiceProvider` (`gpio.i2c`) and `SPIServiceProvider` (`gpio.spi`) and merges `config/gpio.php`.[^provider] UART, PWM: manifests only. No dock, no About section.

Default driver `none` per protocol: every open throws "No … connection driver is configured".[^config] Adapters `extend()` the managers: `microscrap/scrapyard-linux` as `native`, `microscrap/scrapyard-usb` as `usb`.

# Lifecycle

Same for all three protocols.[^i2c-driver][^digital-driver]

- `connectTo($device)->register()` opens the bus/chip, stores the handle in `connections`. Twice → "already connected".
- Driver hands out one transport per `"<device>:<pin|address|chip select>"`; closed one → fresh one on next request.
- Transport `close()` releases only itself (Linux line request; I2C slave holds nothing). Calls after close throw.
- `disconnect($device)` closes every transport on it, then `closeConnection($handle)`, then forgets it. `connectTo()` works again.

# I2C specifics

- `bulkWrite`: each chunk = own message. Linux `I2C_RDWR`, 42 per transfer (kernel cap; `I2CRdwrIoctlMaxMsgs::MAX_MESSAGES_PER_TRANSFER`). MPSSE: repeated START per chunk, one STOP.
- Linux: one `/dev/i2c-N` fd per bus shared by all slaves; plain `read`/`write` re-select the slave address each call.
- FT232H: `MpsseI2CConnectionDriver` opens the context in I2C mode, shares it with the usb DigitalIO driver (GPIOH pins stay usable). I2C `disconnect()` closes digital pins, then the context. DigitalIO closes only contexts it opened (GPIO mode).
- `via(?string $target)` → promises for `write read writeRead bulkWrite run(BusJob)`; results = blocking results, rejections = blocking throws.
  - Linux: `BusGig` to a work target (pool default); worker keeps one driver per class, buses open.
  - MPSSE: job in a loop fiber; transactions recorded (`MPSSE::record`) and sent by `MpssePump`. Every USB exchange on the context takes a FIFO turn there. ACK check before a repeated START = segment boundary (`MPSSE::cut`), so the wire stops where blocking stops. Job fibers wait with `until()`, never `Promise::wait()`. Named target refused.
  - `BusQueue` per key, one in flight: Linux per slave, MPSSE per bridge. Blocking call waits for the jobs queued before it, not for idle.
  - `close()` refuses `via()` at once; a `via()` handle checks its slave every call. `queue` target refused (resolves a job id).
  - Blocking call on a busy bus waits for the bus, pool worker transfers included. Big offloaded transfers on a bus → other slaves use `via()` too.
- Message cap 8192 bytes, both adapters (i2c-dev's own cap).

# SPI specifics

- `read write transfer writeRead`; `select(Closure)` holds one slave's chip select for every call inside (bus is that slave's meanwhile; others throw on the same stack, wait from another fiber); `speed(int)` per slave. Mode, bit order, word size per connection. `clock()` is the slave's own Hz, or null while it runs at the connection's.
- `via(?string $target)` → promises for `write read transfer writeRead run(BusJob)`; results = blocking results, rejections = blocking throws. Hold chip select off-thread: `run()` a job that calls `select()`. Shared plumbing: `OffloadsBusJobs`, refusals on `GPIOLevelException` (`noEventLoop` / `noWorkTargets` / `offloadTargetDiscardsResult`).[^spi-driver]
  - One `BusQueue` per bus (a job may hold chip select). `SPIBusGig` carries `SPIBusSettings` plus the slave's clock so a worker opens the bus the way this process did and runs at that clock.
  - A `select()` outside the queue pauses it and waits for the running job. A blocking call waits for jobs queued on the bus before it, and for a `select()` running elsewhere — even on that same slave — instead of joining the selection.
  - `close()` refuses `via()` at once; a `via()` handle checks its slave every call. `disconnect()` drops that bus's queue, hold and settings (bus `1` never takes bus `11`). `queue` target refused. A bus registered without its factory cannot be offloaded (`busSettingsUnknown`).
  - Linux: `SPIBusGig` with bus settings + slave clock. Every transfer carries clock and word size (spidev keeps them per device for all fds). `SpidevBusLock` = `flock` on `/run/lock/scrapyard-spi<bus>.lock`, every call, whole `select()`, and `spi_open` (`during()`): another process's open can drop chip select between bufsiz messages.
  - MPSSE: jobs in loop fibers on `MpssePump` (over `MpsseLink`). `select()` = one turn, one recording, one segment per call, tail sent on throw. Lost exchange → chip select up again, clock resent.

# Related

* [integrated-circuits.md](integrated-circuits.md)
* [packaging.md](packaging.md)

[^provider]: ScrapyardIOServiceProvider
[^config]: gpio.php
[^i2c-driver]: I2CConnectionDriver
[^digital-driver]: DigitalIOConnectionDriver
[^spi-driver]: SPIConnectionDriver
