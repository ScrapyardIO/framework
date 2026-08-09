---
type: Component
title: broadcasting
description: fabricate/broadcasting — Channel / PrivateChannel / InteractsWithSockets helpers; broadcaster drivers deferred.
resource: src/Fabricate/Broadcasting/
tags: [component, broadcasting, channels, websockets]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T08:30:00Z" }
status: draft
sources:
  - id: channel
    resource: src/Fabricate/Broadcasting/Channel.php
    title: Channel
  - id: private
    resource: src/Fabricate/Broadcasting/PrivateChannel.php
    title: PrivateChannel
  - id: sockets
    resource: src/Fabricate/Broadcasting/InteractsWithSockets.php
    title: InteractsWithSockets trait
  - id: contracts
    resource: src/Fabricate/Contracts/Broadcasting/
    title: ShouldBroadcast + HasBroadcastChannel
  - id: composer
    resource: src/Fabricate/Broadcasting/composer.json
    title: fabricate/broadcasting manifest
  - id: root
    resource: composer.json
    title: Umbrella replace
  - id: ownership
    resource: .okf/conventions/dependency-direction.md
    title: Env write / Broadcasting peer rules
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/broadcasting`[^composer] |
| Path | `src/Fabricate/Broadcasting/` |
| PHP namespace | `Fabricate\Broadcasting\` |
| Contracts | `Fabricate\Contracts\Broadcasting\*`[^contracts] |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain — Core-free; no provider / MagicAlias yet |

# Scope (0.7.x)

**Shipped:** channel naming helpers and the socket-exclusion trait.

| Type | Role |
|------|------|
| `Channel` | Stringable channel name |
| `PrivateChannel` | Prefixes `private-`; accepts string or `HasBroadcastChannel` |
| `InteractsWithSockets` | `dontBroadcastToCurrentUser()` / `broadcastToEveryone()` (clears `$socket`) |

**Not shipped yet:** Broadcaster manager, drivers (Pusher/Ably/Redis/log/null), `BroadcastServiceProvider`, `Broadcast` MagicAlias, `Dispatchable::broadcast()`, websocket auth routes, or `.env` install writers (see [dependency-direction](../conventions/dependency-direction.md)).[^ownership]

Contracts `ShouldBroadcast` / `HasBroadcastChannel` live under `fabricate/contracts` for future Events integration.

# Packaging

Split package `fabricate/broadcasting` requires `fabricate/contracts` only. Umbrella replaces at `self.version`.

# Verification

`tests/Broadcasting/BroadcastingTest.php` — Channel / PrivateChannel / trait smoke.

# Related

- [events](events.md) — sync dispatcher; broadcast dispatch deferred
- [dependency-direction](../conventions/dependency-direction.md) — Broadcasting ↔ Filesystem for `.env` writes
- [composer-replace](../conventions/composer-replace.md)

[^composer]: fabricate/broadcasting manifest
[^contracts]: ShouldBroadcast + HasBroadcastChannel
[^root]: Umbrella replace
[^ownership]: Env write / Broadcasting peer rules
