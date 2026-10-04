---
type: Concept
title: GPIO protocols
description: Protocol managers under gpio.protocols, the shared connection lifecycle, and what 0.10 ships (Digital, I2C, SPI, PWM, UART).
tags: [gpio, protocols, i2c, digital, spi, pwm, uart]
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
  - id: pwm-driver
    resource: src/GeneralPurposeIO/PWM/PWMConnectionDriver.php
    title: PWMConnectionDriver
  - id: uart-transport
    resource: src/GeneralPurposeIO/UART/UARTTransport.php
    title: UARTTransport
---

# Role

0.10 ships five protocols: Digital, I2C, SPI, PWM and UART. `ScrapyardIOServiceProvider` aggregates `DigitalIOServiceProvider` (`gpio.digital`), `I2CServiceProvider` (`gpio.i2c`), `SPIServiceProvider` (`gpio.spi`), `PWMServiceProvider` (`gpio.pwm`) and `UARTServiceProvider` (`gpio.uart`) and merges `config/gpio.php`.[^provider] No protocol is manifest-only. No dock, no About section.

Default driver `none` per protocol: every open throws "No … connection driver is configured".[^config] Adapters `extend()` the managers: `microscrap/scrapyard-linux` as `native`, `microscrap/scrapyard-usb` as `usb`.

# Lifecycle

Same for all five protocols, except UART, where one port is one connection.[^i2c-driver][^digital-driver][^pwm-driver]

- `connectTo($device)->register()` opens the bus/chip, stores the handle in `connections`. Twice → "already connected".
- Driver hands out one transport per `"<device>:<pin|address|chip select>"`; closed one → fresh one on next request.
- Transport `close()` releases only itself (Linux line request; I2C slave holds nothing). Calls after close throw.
- `disconnect($device)` closes every transport on it, then `closeConnection($handle)`, then forgets it. `connectTo()` works again.

# I2C specifics

- `bulkWrite`: each chunk = own message. Linux `I2C_RDWR`, 42 per transfer (kernel cap; `I2CRdwrIoctlMaxMsgs::MAX_MESSAGES_PER_TRANSFER`). MPSSE: repeated START per chunk, one STOP.
- Linux: one `/dev/i2c-N` fd per bus shared by all slaves; plain `read`/`write` re-select the slave address each call.
- FT232H: `MpsseI2CConnectionDriver` opens the context in I2C mode, shares it with the usb DigitalIO driver (GPIOH pins stay usable). I2C `disconnect()` closes digital pins, then the context. DigitalIO closes only contexts it opened (GPIO mode).
- `via(?string $pool)` → promises for `write read writeRead bulkWrite run(BusJob)`; results = blocking results, rejections = blocking throws.
  - Linux: `BusGig` to a worker pool (thread when on, else process); worker keeps one driver per class, buses open.
  - MPSSE: job in a loop fiber; transactions recorded (`MPSSE::record`) and sent by `MpssePump`. Every USB exchange on the context takes a FIFO turn there. ACK check before a repeated START = segment boundary (`MPSSE::cut`), so the wire stops where blocking stops. Job fibers wait with `until()`, never `Promise::wait()`. Named pool refused.
  - `BusQueue` per key, one in flight: Linux per slave, MPSSE per bridge. Blocking call waits for the jobs queued before it, not for idle.
  - `close()` refuses `via()` at once; a `via()` handle checks its slave every call.
  - Blocking call on a busy bus waits for the bus, pool worker transfers included. Big offloaded transfers on a bus → other slaves use `via()` too.
- Message cap 8192 bytes, both adapters (i2c-dev's own cap).

# SPI specifics

- `read write transfer writeRead`; `select(Closure)` holds one slave's chip select for every call inside (bus is that slave's meanwhile; others throw on the same stack, wait from another fiber); `speed(int)` per slave. Mode, bit order, word size per connection. `clock()` is the slave's own Hz, or null while it runs at the connection's.
- `via(?string $pool)` → promises for `write read transfer writeRead run(BusJob)`; results = blocking results, rejections = blocking throws. Hold chip select off-thread: `run()` a job that calls `select()`. Shared plumbing: `OffloadsBusJobs`, refusals on `GPIOLevelException` (`noEventLoop` / `noWorkerPools`).[^spi-driver]
  - One `BusQueue` per bus (a job may hold chip select). `SPIBusGig` carries `SPIBusSettings` plus the slave's clock so a worker opens the bus the way this process did and runs at that clock.
  - A `select()` outside the queue pauses it and waits for the running job. A blocking call waits for jobs queued on the bus before it, and for a `select()` running elsewhere — even on that same slave — instead of joining the selection.
  - `close()` refuses `via()` at once; a `via()` handle checks its slave every call. `disconnect()` drops that bus's queue, hold and settings (bus `1` never takes bus `11`). A bus registered without its factory cannot be offloaded (`busSettingsUnknown`).
  - Linux: `SPIBusGig` with bus settings + slave clock. Every transfer carries clock and word size (spidev keeps them per device for all fds). `SpidevBusLock` = `flock` on `/run/lock/scrapyard-spi<bus>.lock`, every call, whole `select()`, and `spi_open` (`during()`): another process's open can drop chip select between bufsiz messages.
  - MPSSE: jobs in loop fibers on `MpssePump` (over `MpsseLink`). `select()` = one turn, one recording, one segment per call, tail sent on throw. Lost exchange → chip select up again, clock resent.

# PWM specifics

- Transport = one channel on one chip (`"<chip>:<channel>"`). `get/set` Period, DutyCycle (ns), Enable, Polarity (`true` = inversed); a set answers the read-back.[^pwm-driver]
- Linux (`scrapyard-linux`, `native`): sysfs under `/sys/class/pwm`, no ext-posi. `device()` exports a missing channel and waits for udev to hand its attributes over (`readyTimeout()`, default 500 ms): on the loop when one is bound, a 10 ms sleep otherwise. `close()` disables and unexports.
- `via(?string $pool)` → promises for the eight calls + `run(BusJob)`. One queue per channel. `PWMChannelGig` builds the worker's driver from `workerArguments()` (Linux: sysfs root), so a worker writes the same tree.

# UART specifics

- One port per connection: `close()` closes the connection too; `connectTo()` again after.[^uart-transport]
- One unread buffer per port (64 KB, oldest dropped, `dropped()` counts). `read($n, $timeout_ms)` → `[]` on timeout; `readUntil($delimiter, $timeout_ms)` → `null` on timeout; `write($data, $timeout_ms)` → every byte to the OS in 256-byte chunks or throws with how far it got; `dtr()`/`rts()`.
- One drain a pass (FTDI reads sized to one 10 ms interval): a streaming device never holds a read or the loop. Writes go out whole, in arrival order. A hung-up tty (poll ready, 0 bytes twice) throws `readFailed`; a loop intake failure goes to the waiting call; timeout counts ride on `UARTException::$sent`/`$total`.
- Loop bound → waits suspend a fiber / borrow the loop; `watch()` mails `UARTReceived` (`gpio.uart.<device>`, base64 bytes on the wire). No `via()`.
- Linux: VMIN=0, `ppoll` for bytes and `POLLOUT` for room, fd to the loop through `posix_fdopen`, `O_CLOEXEC`, modem lines via TIOCMBIS/TIOCMBIC (a pty has none).
- USB: sampled every 10 ms on a loop, latency 1 ms, async sends, lost device = empty read + negative modem status, DTR/RTS released at open, XON/XOFF characters set. `FtdiBridge`: one engine per FTDI interface (UART or MPSSE); UART on FT2232H/FT4232H = channel A.

# Related

* [integrated-circuits.md](integrated-circuits.md)
* [packaging.md](packaging.md)

[^provider]: ScrapyardIOServiceProvider
[^config]: gpio.php
[^i2c-driver]: I2CConnectionDriver
[^digital-driver]: DigitalIOConnectionDriver
[^spi-driver]: SPIConnectionDriver
[^pwm-driver]: PWMConnectionDriver
[^uart-transport]: UARTTransport
