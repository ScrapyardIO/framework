---
type: Module
title: events
description: fabricate/events — domain Dispatcher (listen/dispatch/subscribe/defer); Core owns Event alias (fakes), EventServiceProvider, DiscoverEvents, Dispatchable, Workshop event:* / make:listener.
resource: src/Fabricate/Events/
tags: [component, events, dispatcher]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T06:10:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: composer
    resource: src/Fabricate/Events/composer.json
    title: fabricate/events manifest
  - id: dispatcher
    resource: src/Fabricate/Events/Dispatcher.php
    title: Domain Dispatcher
  - id: null-dispatcher
    resource: src/Fabricate/Events/NullDispatcher.php
    title: NullDispatcher
  - id: contract
    resource: src/Fabricate/Contracts/Events/Dispatcher.php
    title: Dispatcher contract
  - id: provider
    resource: src/Fabricate/Core/Providers/EventServiceProvider.php
    title: Core EventServiceProvider
  - id: discover
    resource: src/Fabricate/Core/Events/DiscoverEvents.php
    title: DiscoverEvents
  - id: dispatchable
    resource: src/Fabricate/Core/Events/Dispatchable.php
    title: Dispatchable trait
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Event.php
    title: Event MagicAlias
  - id: fake
    resource: src/Fabricate/Testing/Fakes/EventFake.php
    title: EventFake
  - id: assembly
    resource: src/Fabricate/Core/Setup/AssemblyLine.php
    title: AssemblyLine::withEvents
  - id: root
    resource: composer.json
    title: Umbrella replace
  - id: ownership
    resource: .okf/conventions/magic-aliases.md
    title: MagicAlias / provider ownership
  - id: dep
    resource: .okf/conventions/dependency-direction.md
    title: Dependency direction
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/events`[^composer] |
| Path | `src/Fabricate/Events/` |
| PHP namespace | `Fabricate\Events\` |
| Contracts | `Fabricate\Contracts\Events\*`[^contract] |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain — Core-free; types `ServiceContainer`; Core binds `events` + alias + provider[^ownership][^dep] |

Ported from Illuminate / 0.6 donor with ownership correction: **no** `CallQueuedListener`, **no** Bus/Queue/tx after-commit, **no** broadcast.

# How it works

## Mental model

1. **Domain** `Dispatcher` — sync listen / dispatch / until / subscribe / wildcards / `defer()` buffering. Resolves class listeners via `ServiceContainer::make`.
2. **NullDispatcher** — wraps a real dispatcher; swallows `dispatch`/`until`/`push` but still forwards `listen` and inspection helpers.
3. **Core glue** — `Machine` singleton `'events'`; aliases to concrete + contract; `Event` MagicAlias with `fake` / `fakeExcept` / `fakeFor` / `fakeExceptFor`; `EventServiceProvider` via `AssemblyLine::withEvents()` on booting.
4. **Testing** — `Fabricate\Testing\Fakes\EventFake` implements the Dispatcher contract + `Fake` marker; records immediately (no DB after-commit path).
5. **Bootstrap** — `bootstrapWith` dispatches `bootstrapping:` / `bootstrapped:` strings; Workshop / ConsoleKernel wire Symfony command events → `CommandStarting` / `CommandFinished`; `Terminating` on kernel terminate.

```php
$dispatcher = app('events');
$dispatcher->listen('order.placed', fn () => /* … */);
$dispatcher->dispatch('order.placed');

Event::listen(OrderPlaced::class, SendInvoice::class);
Event::dispatch(new OrderPlaced($order));

event(new OrderPlaced($order));

Event::fake();
OrderPlaced::dispatch($order);
Event::assertDispatched(OrderPlaced::class);
```

## Out of scope (this restore)

| Piece | Why |
|-------|-----|
| `CallQueuedListener` / `QueuedClosure` / `InvokeQueuedClosure` | Needs Bus/Queue |
| `queueable()` / queue + transaction resolvers | Needs Queue + DB |
| After-commit wiring | Needs DB transactions |
| `ShouldBroadcast` / `Dispatchable::broadcast` | Needs Broadcasting |
| `EventGenerateCommand` | Generator not restored |
| Observer Eloquent activation | Needs Eloquent |

Queue/broadcast scaffolding comments remain in `Dispatcher` for staged enablement.

## API (domain)

| Symbol | Role |
|--------|------|
| `Dispatcher` | Sync event bus + `defer()` / `getRawListeners()`[^dispatcher] |
| `NullDispatcher` | Non-firing decorator (`ForwardsCalls`)[^null-dispatcher] |
| `Contracts\Events\Dispatcher` | Swap surface (includes `defer`, `getRawListeners`, `hasWildcardListeners`)[^contract] |
| `ShouldBeDiscovered` | Opt-out of auto-discovery |
| `ShouldDispatchAfterCommit` / `ShouldHandleEventsAfterCommit` | Markers for future DB commit hooks |

## Core surface

| Symbol | Role |
|--------|------|
| `MagicAliases\Event` | Accessor `events` + fake helpers[^alias] |
| `Providers\EventServiceProvider` | `$listen` / subscribe / discovery; uses `$this->container`[^provider] |
| `Events\DiscoverEvents` | Listener directory scan[^discover] |
| `Events\Dispatchable` | `dispatch` / `dispatchIf` / `dispatchUnless` via `event()` (no `broadcast`)[^dispatchable] |
| `Events\Terminating` | Kernel terminate signal |
| `Testing\Fakes\EventFake` | Assertion fake; `MagicAlias::isFake()`[^fake] |
| `Console\EventCacheCommand` / `EventClearCommand` / `EventListCommand` | Workshop `event:*` |
| `Console\ListenerMakeCommand` | `make:listener` (typed + plain stubs; no `--queued`) |
| `AssemblyLine::withEvents()` | Registers provider on booting[^assembly] |
| `Machine::eventsAreCached()` / `getCachedEventsPath()` | Event discovery cache |
| `event()` helper | Dispatches via `app('events')` |

# Related

- [contracts](contracts.md) — `Contracts\Events\*`
- [core](core.md) — Machine / AssemblyLine / ConsoleKernel wiring
- [console](console.md) — WorkshopStarting / CommandFinished / event:* commands
- [MagicAlias and provider ownership](../conventions/magic-aliases.md)
- [Component dependency direction](../conventions/dependency-direction.md)
- [Composer replace](../conventions/composer-replace.md)

[^composer]: fabricate/events manifest
[^dispatcher]: Domain Dispatcher
[^null-dispatcher]: NullDispatcher
[^contract]: Dispatcher contract
[^provider]: Core EventServiceProvider
[^discover]: DiscoverEvents
[^dispatchable]: Dispatchable trait
[^alias]: Event MagicAlias
[^fake]: EventFake
[^assembly]: AssemblyLine::withEvents
[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
[^dep]: Dependency direction
