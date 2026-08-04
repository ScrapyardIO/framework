---
type: Trap
title: env() outside config
description: Calling env() from sketches/services after config cache yields null — use config() only at runtime.
tags: [trap, config]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-04T04:21:00Z" }
status: stable
sources:
  - id: config-concept
    resource: ../core/configuration.md
    title: Configuration concept
---

# Rule

Same as Laravel: after `php workshop config:cache`, `env('X')` outside config files is unreliable/null. Runtime code uses `config('...')` only.[^config-concept]

[^config-concept]: Configuration concept
