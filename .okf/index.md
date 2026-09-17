---
okf_version: "0.2"
---

# scrapyard-io/framework — knowledge bundle

GPIO framework for Venusian. Protocol transports (Digital, I2C, SPI, UART, PWM; Analog scaffold), the Circuits registry, one `gpio` IOPool resource. Chip drivers live in `dept-of-scrapyard-robotics/*`.

Read this index first, then only the concepts the task needs. Every concept is `status: draft` until a human verifies it.

# Concepts

* [overview.md](/overview.md) - what ships at 0.8.0, tree, counts, stack position
* [gpio-protocols.md](/gpio-protocols.md) - protocol managers, adapters, buses, About inventory
* [circuits.md](/circuits.md) - catalog, PendingCircuit fluent, profiles, taxonomy
* [dock-resource.md](/dock-resource.md) - the `gpio` resource: watch / receive / defer / every / stream, mail species, laws
* [packaging.md](/packaging.md) - nine `gpio/*` splits, Core unsplit, dependency direction
* [known-gaps.md](/known-gaps.md) - Analog scaffold, PWM disabled, CI without exts, deliberate moves

# Log

* [log.md](/log.md)
