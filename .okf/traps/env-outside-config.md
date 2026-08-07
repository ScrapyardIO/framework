---
type: Trap
title: env() outside config
description: Call env() only inside config files; use config() / Repository at runtime once config is bound.
tags: [trap, config, env]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T02:22:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: machine-config
    resource: config/machine.php
    title: machine config (uses env)
  - id: helpers
    resource: src/Fabricate/Core/Helpers/helpers.php
    title: config() helper
  - id: nab-env
    resource: src/Fabricate/NutsAndBolts/Env.php
    title: Nab Env (read-side)
  - id: config
    resource: .okf/components/config.md
    title: Config Repository component
---

# Rule

1. **`env()`** — only inside PHP config files (e.g. `config/machine.php`).[^machine-config]
2. **Runtime** — read via `config('key')` or the bound `Repository` (`app('config')` / `$machine->config`).[^helpers][^config]

Do not call `env()` from libraries, ServiceProviders (except when merging config files), or command handlers — config cache would freeze wrong values.

# Current 0.7 reality

| Piece | Status |
|-------|--------|
| `fabricate/config` Repository | Ported[^config] |
| `config()` helper in Core | Present (needs `app()` + `config` binding)[^helpers] |
| Nab `Env` / `env()` | Read-side on Nab[^nab-env] |
| `LoadConfiguration` bootstrapper | Still reconstituting — binding `config` may not run on every CLI path yet |

Until LoadConfiguration is on the ConsoleKernel bootstrapper list, `config()` can fail even though the Config component exists.

# Related

- [config](../components/config.md)
- [core](../components/core.md)

[^machine-config]: machine config (uses env)
[^helpers]: config() helper
[^nab-env]: Nab Env (read-side)
[^config]: Config Repository component
