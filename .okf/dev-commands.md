---
type: Module
title: scrapyard:ext and scrapyard:modules
description: Dev commands that set a machine up for ScrapyardIO — ext-posi and ext-ftdi through PIE, then the microscrap packages the OS and loaded extensions can run, through Composer.
resource: src/GeneralPurposeIO/Core/
tags: [console, computer, extensions, pie, composer, microscrap, workflows]
status: draft
generated: { by: claude-opus-5-5/claude-code, at: "2026-10-04T23:40:00Z" }
sources:
  - id: provider
    resource: src/GeneralPurposeIO/Core/Providers/ScrapyardIOServiceProvider.php
    title: ScrapyardIOServiceProvider
  - id: ext
    resource: src/GeneralPurposeIO/Core/Extensions/
    title: ExtensionsFlow, ScrapyardExtension and the nodes
  - id: modules
    resource: src/GeneralPurposeIO/Core/Modules/
    title: ModulesFlow, MicroscrapModule and the nodes
  - id: host
    resource: src/GeneralPurposeIO/Core/Host.php
    title: Host
  - id: tests
    resource: tests/Core/
    title: Pest coverage for both interviews
  - id: install-ext
    resource: venusian/framework:src/Voyager/Core/Extensions/
    title: venusian/framework install:ext, the pattern scrapyard:ext follows
---

# Use

```bash
php computer scrapyard:ext                    # list, pick, install through PIE
php computer scrapyard:ext ftdi               # that one, no list
php computer scrapyard:modules                # list, pick, composer require
php computer scrapyard:modules scrapyard-usb  # that one, no list
```

Both registered by `ScrapyardIOServiceProvider::registerCommands()`: each class bound as a singleton, then `commands()`. The console builds its list through Symfony's `ContainerCommandLoader`, whose `has()` asks the container, so an unbound command never lists.[^provider] Names: `install:ext` belongs to venusian/framework.

Exit 0: everything asked for `installed`, nothing to install, or nothing selected. Exit 1 otherwise. Non-interactive run with no name ends with the list of names.

# scrapyard:ext

Same interview as venusian's `install:ext`[^install-ext] over `ScrapyardExtension`: `php-io-extensions/posi:^0.10`, `php-io-extensions/ftdi:^0.10`.[^ext]

```
ExtensionStateNode → PhpConfigFinderNode → PieFinderNode → ExtensionSelectNode → ExtensionInstallNode
                                               └─ install-pie → PieInstallNode ─┘
```

* State: Windows → `not on Windows` (both `php-ext` manifests exclude it). Loaded on the ^0.10 line (`ScrapyardExtension::accepts()`) → `installed`. Loaded off the line (0.9.x) → installable; the row reads `ftdi (0.9.0 loaded, replaced by ^0.10)`.
* PIE always `[PHP_BINARY, pie, …]`; php-config matched to that binary; PIE found on PATH or `~/.local/bin`, else offered as a download + `self-verify`. Rules as install:ext.
* Check after each install: `[php, -r, "echo phpversion('<ext>');"]`. Outcomes: `installed` · `failed (PIE exit code N)` · `failed (<message>)` · `installed by PIE, but {php} does not load it` · `installed by PIE, but {php} loads <version>`.

# scrapyard:modules

`ModulesFlow`: `ModuleStateNode → ComposerFinderNode → ModuleSelectNode → ModuleInstallNode`.[^modules] Bag in also carries `base_path` (the app root).

| Module | Needs |
|---|---|
| `gpio`, `i2c`, `spi`, `uart` | ext-posi ^0.10 |
| `mpsse` | ext-ftdi ^0.10 |
| `scrapyard-linux` | Linux, ext-posi ^0.10 |
| `scrapyard-usb` | ext-ftdi ^0.10 |

Reasons, first that applies: `Linux only` · `needs ext-<x>` · `needs ext-<x> ^0.10, <version> is loaded` · `installed` (`Composer\InstalledVersions`). Same ext lines the microscrap manifests require.

* Composer: `composer` on PATH, then `composer.phar` in the app root; counts only if `[PHP_BINARY, composer, --version]` prints `Composer`. Run by `PHP_BINARY` so Composer checks `ext-*` against the PHP running computer, not whichever `php` its shebang finds. None → ends with a note; never downloaded.
* Install: one `[php, composer, require, <each>:^0.10, --working-dir=<app>]` on the terminal. One resolve: all land or none. Outcome per module: `installed` · `failed (Composer exit code N)` · `failed (<message>)`.

# Prompt

Plain `laravel/prompts` `multiselect` + a `note` naming the rows that cannot be installed and why; installable rows start selected. Not venusian's `ChecklistPrompt`: published `venusian-voyager/console` 0.10.0 does not ship it.

# Host

`Core\Host`: PHP binary, OS family, `extensionVersion()` (`phpversion()`), `installed()` (Composer runtime), home, `exists()`, executable lookup, download, `run()` over `Voyager\Process\Factory`. Tests subclass it (`tests/Fixtures/DescribedHost`) and fake the factory.[^host][^tests]

# Not covered by Pest

Real PIE and Composer runs, downloads, sudo prompts, the commands' `handle()`. Proved by hand 2026-10-04 in a 0.10 app copy on macOS (php84): both listed; `scrapyard:ext ftdi` replaced a loaded 0.9.0 with 0.10.0 through PIE; `scrapyard:modules mpsse` and `uart` installed through Composer; `scrapyard-linux` refused as `Linux only`.

[^provider]: ScrapyardIOServiceProvider
[^ext]: ExtensionsFlow, ScrapyardExtension and the nodes
[^modules]: ModulesFlow, MicroscrapModule and the nodes
[^host]: Host
[^tests]: Pest coverage for both interviews
[^install-ext]: venusian/framework install:ext, the pattern scrapyard:ext follows
