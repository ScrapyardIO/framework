---
type: Module
title: console
description: fabricate/console — Workshop CLI (Command, IO components, prompts, scheduling, WorkshopInstance).
resource: src/Fabricate/Console/
tags: [component, console, workshop, cli]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T03:30:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: composer
    resource: src/Fabricate/Console/composer.json
    title: fabricate/console manifest
  - id: command
    resource: src/Fabricate/Console/Command.php
    title: Command
  - id: workshop
    resource: src/Fabricate/Console/WorkshopInstance.php
    title: WorkshopInstance (CLIMachine)
  - id: console-program
    resource: src/Fabricate/Console/ConsoleProgram.php
    title: ConsoleProgram (Events-full Application; donor)
  - id: resources
    resource: src/Fabricate/Console/resources/views/components/
    title: Termwind component views
  - id: provider
    resource: src/Fabricate/Core/Providers/ConsoleSupportServiceProvider.php
    title: Core ConsoleSupportServiceProvider
  - id: workshop-provider
    resource: src/Fabricate/Core/Providers/WorkshopServiceProvider.php
    title: Core WorkshopServiceProvider
  - id: kernel
    resource: src/Fabricate/Core/Console/ConsoleKernel.php
    title: ConsoleKernel
  - id: ownership
    resource: .okf/conventions/magic-aliases.md
    title: MagicAlias / provider ownership
  - id: root
    resource: composer.json
    title: Umbrella replace
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/console`[^composer] |
| Path | `src/Fabricate/Console/` |
| PHP namespace | `Fabricate\Console\` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain — Core-free providers; Core owns Workshop/ConsoleSupport providers + `Workshop` MagicAlias[^ownership] |

Ported from 0.6 donor (full tree + `resources/views/components/*`). **No** `*ServiceProvider` in this package.

# How it works

## Mental model

| Piece | Role |
|-------|------|
| `Command` | Symfony command + Fabricate IO/prompts/signals; types `ServiceContainer`[^command] |
| `WorkshopInstance` | Symfony Application implementing `CLIMachine` — used by Core `ConsoleKernel`; takes `Dispatcher` and dispatches `WorkshopStarting`[^workshop][^kernel] |
| `ConsoleProgram` | Fuller Application (donor-era); WorkshopInstance is the 0.7 CLI path[^console-program] |
| View `Components\*` + `resources/views` | Termwind-rendered console UI[^resources] |
| `Scheduling\*` | Schedule/mutex/events — depends on Cache/Bus/Queue contracts (not all restored yet) |
| Attributes / Parser / OutputStyle | Signature parsing + styled IO |

```php
class HelloCommand extends \Fabricate\Console\Command
{
    protected ?string $signature = 'hello {name}';

    public function handle(): int
    {
        $this->components->info('Hello '.$this->argument('name'));
        return self::SUCCESS;
    }
}
```

## Runtime wiring (Core — not this package)

| Piece | Home |
|-------|------|
| `CLIKernel` binding | AssemblyLine `withKernels()` → `ConsoleKernel`[^kernel] |
| `cliMachine()` | `Machine` → `WorkshopInstance::class` |
| ConsoleSupport / Workshop providers | `Fabricate\Core\Providers\*`[^provider][^workshop-provider] |
| `Workshop` MagicAlias | `Fabricate\Core\MagicAliases\Workshop` → `CLIKernel` |
| Default providers | `DefaultProviders` includes `ConsoleSupportServiceProvider` |

# Requires

```json
"laravel/prompts": "^0.3",
"nunomaduro/termwind": "^2.0",
"fabricate/contracts|macroable|collections|conditionable": "^0.7.0",
"symfony/console": "^7.4 || ^8.0",
"symfony/process": "^7.4.5 || ^8.0.5"
```

Also pulled Nab `Stringable` / `Tappable` (used by components + scheduling).[^composer]

# Gaps (honest)

- `ConsoleProgram` still typehints Events `Dispatcher` — prefer `WorkshopInstance` until Events is ported.
- Scheduling mutexes use Cache (`file`/`redis`); `job()` still needs Queue/Bus.
- `WorkshopServiceProvider` registers: `about` (Environment/Cache/Drivers only; packages extend via `AboutCommand::add()` — no ICs/hardware in Core), `config:*`, `env`, `key:generate`, `package:discover`, `cache:clear`/`forget`, `optimize` / `optimize:clear` (config+events; packages via `ServiceProvider::optimizes()`), nine `schedule:*` commands, and dev generators (`make:*`, `vendor:publish`).
- Providers are **not** deferred while Signals must always load.
- Scheduling uses Nab `Carbon` (not Core `Date` MagicAlias) to keep Console Core-free.

# Related

- [core](core.md) — ConsoleKernel / providers / Workshop alias
- [contracts](contracts.md) — `CLIKernel`, `CLIMachine`, `Isolatable`, `PromptsForMissingInput`
- [filesystem](filesystem.md) — `GeneratorCommand` uses Filesystem
- [MagicAlias ownership](../conventions/magic-aliases.md)

[^composer]: fabricate/console manifest
[^command]: Command
[^workshop]: WorkshopInstance (CLIMachine)
[^console-program]: ConsoleProgram (Events-full Application; donor)
[^resources]: Termwind component views
[^provider]: Core ConsoleSupportServiceProvider
[^workshop-provider]: Core WorkshopServiceProvider
[^kernel]: ConsoleKernel
[^ownership]: MagicAlias / provider ownership
[^root]: Umbrella replace
