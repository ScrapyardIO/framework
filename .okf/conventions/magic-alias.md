---
type: Convention
title: MagicAlias
description: Container-backed static proxies under Fabricate\NutsAndBolts\MagicAliases — the Fabricate analogue of Laravel facades.
tags: [convention, terminology, di]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
status: draft
sources:
  - id: magic-aliases
    resource: src/Fabricate/NutsAndBolts/MagicAliases
    title: MagicAliases directory
  - id: register
    resource: src/Fabricate/Core/Bootstrap/RegisterMagicAliases.php
    title: RegisterMagicAliases bootstrap
---

# What it is

A **MagicAlias** is an abstract static proxy that resolves a service from the application container (`Program` / Machine). Concrete aliases live under `Fabricate\NutsAndBolts\MagicAliases\` (for example `App`, `Config`, `Cache`, `Log`, `Circuit`, `Sensor`, `Display`).[^magic-aliases]

They are registered during console/application bootstrap via `Fabricate\Core\Bootstrap\RegisterMagicAliases`.[^register]

# Usage guidance

- Prefer MagicAlias classes for static access to container-bound services in application and framework code.
- Resolve through the container or constructor injection when a static proxy is unnecessary.
- Do not confuse MagicAlias with fluent builder/collaborator APIs that are sometimes called “facades” in casual conversation — those are unrelated patterns.

[^magic-aliases]: MagicAliases directory
[^register]: RegisterMagicAliases bootstrap
