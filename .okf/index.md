---
okf_version: "0.2"
---

# scrapyard-io/framework — knowledge bundle

GPIO framework for Venusian. Five protocol transports (Digital, I2C, SPI, UART, PWM), the integrated-circuit vocabulary and catalog, one `gpio` IOPool resource. Hardware drivers live in the `microscrap/scrapyard-*` adapters, chip drivers in `dept-of-scrapyard-robotics/*`.

Read this index first, then only the concepts the task needs. Every concept is `status: draft` until a human verifies it.

# Concepts

* [overview.md](/overview.md) - what ships at 0.8.0, tree, counts, stack position
* [gpio-protocols.md](/gpio-protocols.md) - protocol managers, adapters, buses, About inventory
* [integrated-circuits.md](/integrated-circuits.md) - chip kinds, transports, Bootable, DataRegister; the catalog, fluent builder, profiles, make-profile
* [display-panels.md](/display-panels.md) - DisplayPanel base plus WindowAddressable / RefreshesOnCommand / Switchable, RefreshMode, the consumer rule
* [dock-resource.md](/dock-resource.md) - the `gpio` resource: watch / receive / defer / every / stream, mail species, laws
* [packaging.md](/packaging.md) - eight `gpio/*` splits, Core unsplit, dependency direction
* [known-gaps.md](/known-gaps.md) - no standalone install from Packagist, About off, CI without exts

# Log

* [log.md](/log.md)
