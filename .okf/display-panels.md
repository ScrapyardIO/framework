---
type: Concept
title: Display panels
description: DisplayPanel and its three children — the contract a pixel panel exposes so Surface EmbeddedDisplays can draw for it. Base plus WindowAddressable, RefreshesOnCommand, Switchable.
tags: [contracts, integrated-circuits, display, panel, surface]
status: draft
generated: { by: claude-fable-5-1/claude-code, at: "2026-09-18T19:30:00Z" }
sources:
  - id: panel
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/DisplayPanel.php
    title: DisplayPanel
  - id: window
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/WindowAddressable.php
    title: WindowAddressable
  - id: refresh
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/RefreshesOnCommand.php
    title: RefreshesOnCommand
  - id: mode
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/RefreshMode.php
    title: RefreshMode
  - id: switch
    resource: src/GeneralPurposeIO/Contracts/IntegratedCircuits/Switchable.php
    title: Switchable
  - id: tests
    resource: tests/Contracts/DisplayPanelContractsTest.php
    title: contract shape tests
---

# Shape

`DisplayPanel extends IntegratedCircuit`, nothing else. Three verbs of its
own: `width()`, `height()`,
`transmit(int $origin_x, int $origin_y, array $raw_data, ?int $frame_width = null, ?int $frame_height = null): void`.
Null width or height = whole panel. The signature is the one SSD1306 and
ST77xx already had; the contract caught up with the chips.[^panel][^tests]

# Packing is Surface's word, and stays out of here

A panel driver also implements `Surface\Contracts\Framebuffers\FormatSpecification`,
and `EmbeddedDisplayManager::attach()` asks for the intersection
`DisplayPanel&FormatSpecification`. `DisplayPanel` briefly extended it; that
put `use Surface\...` inside `gpio/contracts`, which every protocol split
and both `scrapyard-*` adapters require — a PWM fan board would have pulled
in a graphics package to say nothing about pixels. The direction is one way:
Surface may require `gpio/contracts`, never the reverse. A test walks `src/`
and fails on any `use Surface\` that reappears.

Children, each `extends DisplayPanel`:

| Child | Adds | Who |
|---|---|---|
| `WindowAddressable` | nothing — a marker: `transmit()` honours origin and size | SSD1306, ST7735 / ST7789 / ST7796 |
| `RefreshesOnCommand` | `refresh(RefreshMode $mode = RefreshMode::FULL): void` | ePaper (SSD1608, JD79661, Spectra 6) — no package yet |
| `Switchable` | `setDisplay(bool $on): void` | SSD1306, ST77xx |

`RefreshMode` is string-backed: `FULL = 'full'`, `PARTIAL = 'partial'`.[^mode]

# Why children, not one fat interface

- **NeoPixel strips and matrices, MAX7219 chains, HUB75** have no address
  window: the whole strip streams every time. They implement the base only;
  a consumer sends whole frames.
- **ePaper** separates the RAM write from the visible update, which takes
  seconds and picks a LUT. `transmit()` writes, `refresh()` shows.
- **TFT and OLED** show on write. They never implement `RefreshesOnCommand`.
- Serpentine order and GRB channel order on a NeoPixel matrix are wire
  details the driver permutes on `transmit()` from a row-major B24 buffer.
  Planar ePaper receives its planes concatenated in palette order from one
  Surface `flush()` and splits at the plane size it knows.

# Consumer rule (Surface EmbeddedDisplays)

`WindowAddressable` → send damage regions. Otherwise → whole frame.
`RefreshesOnCommand` → `refresh()` once per frame after the last transmit.
`Switchable` → `setDisplay(false)` on close, and show/hide map to it.

# Not here

A `Maintained` child (periodic VCOM toggle, Sharp memory LCD) is named but
not written: no panel on the bench needs it.

[^panel]: DisplayPanel
[^window]: WindowAddressable
[^refresh]: RefreshesOnCommand
[^mode]: RefreshMode
[^switch]: Switchable
[^tests]: contract shape tests
