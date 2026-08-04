---
type: Playbook
title: First sketch
description: Bootstrap a ScrapyardIO app and create/run a lifecycle sketch via Workshop.
tags: [playbook, sketches]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Installation + Your First Sketch
---

# Steps

1. Require `scrapyard-io/framework`; wire Composer scripts (`ComposerScripts::postAutoloadDump`, `package:discover`) per README.
2. Ensure `bootstrap/app.php` returns `Machine::configure(basePath: ...)->create()`.
3. Ensure `workshop` entrypoint + `app/Sketches/Sketch.php` base class.
4. `chmod +x workshop && composer dump-autoload`
5. `php workshop make:sketch HelloHardware`
6. Implement `boot` / `loop` / `shutdown`; return `SketchLoopResult::STOP` or `CONTINUE`.
7. `php workshop sketch:list` then `php workshop sketch hello-hardware`

# Read first

- [Sketch mental model](../orientation/sketch-mental-model.md)
- [Machine](../core/machine.md)
- [Workshop](../core/workshop.md)

[^readme]: Installation + Your First Sketch
