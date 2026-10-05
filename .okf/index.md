---
okf_version: "0.2"
---

# scrapyard-io/framework — knowledge bundle

GPIO framework for Venusian. Five protocol transports (Digital, I2C, SPI, UART, PWM), the integrated-circuit vocabulary and catalog. Blocking by default; loop-aware waits, `watch()` mail and `via()` offloading when IOPools is bound. Hardware drivers live in the `microscrap/scrapyard-*` adapters, chip drivers in `dept-of-scrapyard-robotics/*`.

Read this index first, then only the concepts the task needs. Every concept is `status: draft` until a human verifies it.

# Concepts

* [overview.md](/overview.md) - what ships at 0.10.0, tree, counts, stack position, CI
* [gpio-protocols.md](/gpio-protocols.md) - protocol managers, shared connection lifecycle, what 0.10 ships (Digital, I2C, SPI, PWM, UART)
* [integrated-circuits.md](/integrated-circuits.md) - chip kinds, transports, Bootable, DataRegister; the `circuit` catalog and conjure()
* [display-panels.md](/display-panels.md) - DisplayPanel base plus WindowAddressable / RefreshesOnCommand / Switchable, RefreshMode, the consumer rule
* [packaging.md](/packaging.md) - eight `gpio/*` splits, Core unsplit, dependency direction
* [dev-commands.md](/dev-commands.md) - `scrapyard:ext` (ext-posi / ext-ftdi through PIE) and `scrapyard:modules` (microscrap packages the machine can run, through Composer)

# Log

* [log.md](/log.md)
