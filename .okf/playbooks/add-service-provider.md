---
type: Playbook
title: Add a service provider
description: Register application or hardware services via Fabricate service providers and discovery.
tags: [playbook, providers]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Extending the Framework
---

# Steps

1. Create `App\Providers\...` extending `Fabricate\NutsAndBolts\ServiceProvider` (or package provider in companion package).
2. Register bindings/singletons for circuits, sensors, displays, or app services in `register()` / `boot()` as appropriate.
3. Add the provider class to `bootstrap/providers.php` **or** advertise it via Composer package metadata for discovery.
4. Run `php workshop package:discover` after Composer changes.
5. Confirm resolution from a sketch or `workshop` command.

# Read first

- [Providers & discovery](../core/providers-discovery.md)
- [Ownership](../orientation/ownership.md) — chip code stays in DOSR packages

[^readme]: Extending the Framework
