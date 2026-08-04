---
type: CoreType
title: Workshop
description: Symfony Console CLI entry point for ScrapyardIO apps — workshop script boots Machine and handleCommand.
tags: [core, console, workshop]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Useful Workshop Commands
---

# Role

`workshop` is the Artisan-analogue entrypoint: load autoload → `bootstrap/app.php` Machine → `handleCommand(ArgvInput)`.[^readme]

# High-value commands

```bash
php workshop list
php workshop sketch:list
php workshop sketch <name>
php workshop make:sketch <name>
php workshop make:command <name>
php workshop make:font <name>
php workshop make:framebuffer <name>
php workshop package:discover
php workshop config:show <key>
php workshop config:cache
php workshop config:clear
```

# Related

- [Sketch mental model](../orientation/sketch-mental-model.md)
- [Configuration](configuration.md)

[^readme]: Useful Workshop Commands
