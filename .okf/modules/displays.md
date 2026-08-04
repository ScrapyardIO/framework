---
type: Module
title: Displays
description: Windowed and embedded display types + DisplayRegistry — panel details live in tubes/DOSR.
resource: src/Fabricate/Displays
tags: [modules, displays]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
status: draft
sources:
  - id: dir
    resource: src/Fabricate/Displays
    title: Display, EmbeddedDisplay, WindowedDisplay, DisplayRegistry
  - id: config
    resource: config/displays.php
    title: Default displays config
---

# Owns

- `Display`, `EmbeddedDisplay`, `WindowedDisplay`, `DisplayRegistry`, `DisplaysServiceProvider`
- `config/displays.php` for windowed or embedded displays[^dir]

# Does not own

- Panel drivers / tube abstractions — `scrapyard-io/tubes` + DOSR display packages (SSD1306, ST77xx, …)
- Pixel pushing / GFX drivers — [Rendering](rendering.md)

[^dir]: Display, EmbeddedDisplay, WindowedDisplay, DisplayRegistry
[^config]: Default displays config
