---
type: Concept
title: GPIO protocols
description: Protocol managers under gpio.protocols, the connection lifecycle they share, and what 0.9 ships.
tags: [gpio, protocols, i2c, digital]
status: draft
generated: { by: claude-opus-5-5/claude-code, at: "2026-09-24T20:00:00Z" }
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
---

# Role

0.9 ships two protocols: Digital and I2C. `ScrapyardIOServiceProvider` aggregates `DigitalIOServiceProvider` (`gpio.digital`) and `I2CServiceProvider` (`gpio.i2c`) and merges `config/gpio.php`.[^provider] SPI, UART, PWM: manifests only. No dock, no About section.

Default driver `none` per protocol: every open throws "No … connection driver is configured".[^config] Adapters `extend()` the managers: `microscrap/scrapyard-linux` as `native`, `microscrap/scrapyard-usb` as `usb`.

# Lifecycle

Same for both protocols.[^i2c-driver][^digital-driver]

- `connectTo($device)->register()` opens the bus/chip, stores the handle in `connections`. Twice → "already connected".
- Driver hands out one transport per `"<device>:<pin|address>"`; closed one → fresh one on next request.
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

# Related

* [integrated-circuits.md](integrated-circuits.md)
* [packaging.md](packaging.md)

[^provider]: ScrapyardIOServiceProvider
[^config]: gpio.php
[^i2c-driver]: I2CConnectionDriver
[^digital-driver]: DigitalIOConnectionDriver
