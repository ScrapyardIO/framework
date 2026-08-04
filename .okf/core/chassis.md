---
type: CoreType
title: Chassis
description: Fabricate DI container (Chassis) — Machine extends Chassis; bindings, singletons, resolution.
tags: [core, di, chassis]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Chassis dependency-injection container
---

# Role

Chassis is the **service container** substrate. `Machine` extends it, so the app instance resolves bindings like a Laravel container (ScrapyardIO terminology: Chassis, not “Application” alone).[^readme]

Use service providers to register hardware and app services — [Providers & discovery](providers-discovery.md).

# Terminology

Static container proxies are preferably **MagicAlias** classes (Laravel Facade analogue). See [MagicAlias terminology](../conventions/magic-alias.md) for the migration-state caveat (some Facade references remain in source).

[^readme]: Chassis dependency-injection container
