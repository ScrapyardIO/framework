---
okf_version: "0.2"
---

# scrapyard-io/framework Knowledge Bundle

Package knowledge for `scrapyard-io/framework` (Fabricate, v0.6.x).
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only.
**Placement:** This bundle lives at the **framework package root** only — not inside `src/Fabricate/*` component folders.
**Links:** Concept cross-links use paths relative to each file.
**Version note:** Identity/version claims track package `0.6.0` / `Machine::VERSION` — revalidate when the package bumps.
**Scope:** Concepts describe this package’s public surface and composition model with companion packages. Chip drivers, native bindings, and device-specific detail belong in those packages’ own documentation / `.okf` bundles.

# Orientation

* [Framework package](orientation/framework.md) - What this package is and is not.
* [Hardware stack layers](orientation/stack-layers.md) - Typical conceptual stack (layers may be skipped).
* [Ownership boundaries](orientation/ownership.md) - What belongs in framework vs companion packages.
* [Sketch mental model](orientation/sketch-mental-model.md) - boot / loop / shutdown cooperative workloads.

# Core runtime

* [Machine](core/machine.md) - Application runtime + container.
* [AssemblyLine](core/assembly-line.md) - Fluent bootstrap builder (`Machine::configure`).
* [Chassis](core/chassis.md) - DI container base.
* [Providers & discovery](core/providers-discovery.md) - Service providers + Composer package discovery.
* [Workshop](core/workshop.md) - Symfony Console entry point (`workshop`).
* [Configuration](core/configuration.md) - `config/`, `env()` only in config files, caching.

# Domain modules

* [Module catalog](modules/catalog.md) - Top-level Fabricate domains in this package.
* [Circuits](modules/circuits.md) - IC registry / factory surface.
* [Sensors](modules/sensors.md) - Sensor abstractions backed by circuits.
* [Displays](modules/displays.md) - Windowed and embedded displays.
* [Rendering](modules/rendering.md) - GFX, fonts, framebuffers, visual presentation.
* [Actuation](modules/actuation.md) - Fans, servos, human-input actuator registries.
* [Supporting services](modules/supporting-services.md) - Cache, queue, Redis, filesystem, log, events, console helpers.

# Conventions

* [Fabricate namespace](conventions/namespace-fabricate.md) - PSR-4 `Fabricate\` under `src/Fabricate/`.
* [Composer replace](conventions/composer-replace.md) - Single package replaces `fabricate/*` components.
* [MagicAlias](conventions/magic-alias.md) - Container-backed static proxies under `NutsAndBolts\MagicAliases`.

# Traps

* [env() outside config](traps/env-outside-config.md) - Same config:cache class of bugs as Laravel apps.

# Playbooks

* [First sketch](playbooks/first-sketch.md) - Bootstrap app + make/run a sketch.
* [Add a service provider](playbooks/add-service-provider.md) - Register app or hardware services.
