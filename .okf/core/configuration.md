---
type: CoreType
title: Configuration
description: PHP array config in config/, read via config(); env() only inside config files; framework defaults merge with app config.
tags: [core, config]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Configuration section
  - id: config-dir
    resource: config
    title: Framework default config files
---

# Rules

- Config files are PHP arrays under `config/`
- Read with `config('machine.name')`, `config('displays.main')`, etc.
- `env()` belongs **in config files only** — not in sketches/services at runtime ([trap](../traps/env-outside-config.md))[^readme]

# Framework defaults (shipped)

Includes (non-exhaustive): `machine.php`, `circuits.php`, `sensors.php`, `displays.php`, `gfx.php`, `actuators.php`, `sketches.php`, `cache.php`, `filesystems.php`, `logging.php`, `queue.php`, `redis.php`.[^config-dir]

Hardware definition docs for circuit params live primarily in DOSR packages; framework holds the registry/config shape.

# Workshop

```bash
php workshop config:show machine
php workshop config:cache
php workshop config:clear
```

[^readme]: Configuration section
[^config-dir]: Framework default config files
