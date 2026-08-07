---
type: Component
title: HTTP client
description: Outbound-only Guzzle client with fluent requests, responses, pools, batches, events, and deterministic fakes.
tags: [component, http, outbound, guzzle, fake]
generated: { by: cursor-agent/gpt-5.6-sol, at: "2026-08-07T07:00:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: client
    resource: src/Fabricate/Http/Client
    title: Outbound HTTP client
  - id: provider
    resource: src/Fabricate/Core/Providers/HttpServiceProvider.php
    title: Deferred http binding
  - id: alias
    resource: src/Fabricate/Core/MagicAliases/Http.php
    title: Http Magic Alias
---

# Scope

The public HTTP surface in 0.7 is the outbound client under `Fabricate\Http\Client\`. It does **not** provide an inbound request/kernel, routing, middleware, authentication, or sessions.

`Fabricate\Http\Resources\Json\JsonResource` is a small compatibility stub consumed by Polisher/Pagination resource transforms. It is not an inbound HTTP or API-resource stack.

# How it works

- `Factory` creates fluent `PendingRequest` instances and owns global middleware/options, fakes, recording, pools, and batches.
- `PendingRequest` builds requests and sends them through Guzzle. It supports headers, JSON/form/multipart bodies, authentication, timeouts, retries, async promises, pools, and batches.
- `Request` and `Response` wrap PSR-7 messages. `Response` exposes status helpers, JSON access, exceptions, headers, cookies, and transfer statistics.
- `RequestSending`, `ResponseReceived`, and `ConnectionFailed` are dispatched when an Events dispatcher is available.
- `Factory::fake()` / `Http::fake()` intercept requests. Sequences, assertions, and `preventStrayRequests()` keep tests deterministic.

# Composition

The component stays Core-free. Core owns:

- deferred `HttpServiceProvider`
- singleton container key `http` and `Factory::class` alias
- `Fabricate\Core\MagicAliases\Http`
- default alias `Http`
- Machine alias `http` → `Fabricate\Http\Client\Factory`

# Packaging

The split package is `fabricate/http`; the umbrella replaces it at `self.version`. Runtime dependencies include `guzzlehttp/guzzle` and `guzzlehttp/uri-template`.

# Verification

`tests/Http/HttpClientTest.php` verifies `Http::fake()` plus an outbound GET and the deferred singleton binding.
