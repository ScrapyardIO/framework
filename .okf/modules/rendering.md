---
type: Module
title: Rendering
description: GFX renderers, fonts, framebuffers, and shared visual presentation for windowed and embedded targets.
resource: src/Fabricate/Rendering
tags: [modules, rendering, gfx, fonts, framebuffers]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
status: draft
sources:
  - id: rendering
    resource: src/Fabricate/Rendering
    title: RenderManager, Renderer, GFX drivers
  - id: framebuffers
    resource: src/Fabricate/Framebuffers
    title: Framebuffer strategies and factories
  - id: fonts
    resource: src/Fabricate/Fonts
    title: Font discovery / console
  - id: gfx-config
    resource: config/gfx.php
    title: gfx config
  - id: readme
    resource: README.md
    title: Hardware and graphics capabilities
---

# Owns

- Rendering: `RenderManager`, `Renderer` / `Renderer2D`, GFX drivers (e.g. PhpdafruitGFX), `RenderingServiceProvider`
- Framebuffers: strategies for full / paged / dirty-region updates
- Fonts: discovery + `make:font` Workshop flow
- Visual presentation helpers on Machine/Core (`VisualManager` / presentation types)
- `config/gfx.php`[^readme]

# Workshop

```bash
php workshop make:font <name>
php workshop make:framebuffer <name>
```

# Related

- [Displays](displays.md)
- microscrap gfx packages (`phpdafruit-gfx`, `sdl3-gfx`, `glfw-gfx`) for binding-level drawing

[^rendering]: RenderManager, Renderer, GFX drivers
[^framebuffers]: Framebuffer strategies and factories
[^fonts]: Font discovery / console
[^gfx-config]: gfx config
[^readme]: Hardware and graphics capabilities
