---
type: CoreType
title: Machine
description: Fabricate\\Core\\Machine — application runtime and service container (extends Chassis); created via Machine::configure()->create().
resource: src/Fabricate/Core/Machine.php
tags: [core, machine]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
status: draft
sources:
  - id: machine
    resource: src/Fabricate/Core/Machine.php
    title: Machine class
  - id: readme
    resource: README.md
    title: Application Structure / bootstrap
---

# Role

`Machine` is both the **application** and the **container**. Startup loads env + config, discovers packages, registers/boots providers, then dispatches the console command.[^readme]

```php
return Machine::configure(basePath: dirname(__DIR__))
    ->create();
```

# Facts

- Extends `Fabricate\Chassis\Chassis`
- Implements program + configuration-cache contracts
- `VERSION` string tracks framework release (e.g. `0.6.0`)[^machine]
- App entry: `bootstrap/app.php` returns a configured Machine
- Console: `workshop` script calls `$app->handleCommand(...)`

# Related

- [AssemblyLine](assembly-line.md) — builder returned by `configure()`
- [Chassis](chassis.md) — DI substrate
- [Workshop](workshop.md)

[^machine]: Machine class
[^readme]: Application Structure / bootstrap
