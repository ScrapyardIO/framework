---
okf_version: "0.2"
---

# scrapyard-io/framework Knowledge Bundle

Package knowledge for `scrapyard-io/framework` (Fabricate, v0.7.x — **reconstituting**).
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only. Concepts below are human-verified `stable` unless marked `deprecated`.
**Placement:** Package-root `.okf/` only — not under `src/Fabricate/*`.
**Links:** Paths relative to each file.
**Version note:** Claims track `0.7.0` while the surface is in flux — update OKF with restores.
**Donor:** 0.6.x kitchen-sink in the workspace is a bring-back reference. Chip/GPIO packages keep their own OKF.

# Orientation

* [Package (0.7)](orientation/package.md) - Umbrella reconstituting Fabricate components + config.
* [NutsAndBolts namespace packaging](orientation/nuts-and-bolts-composition.md) - Shared `Fabricate\NutsAndBolts\` PSR-4 among some support components.
* [Relation to 0.6](orientation/relation-to-0.6.md) - **deprecated** hollow-vs-kitchen-sink framing.

# Components

* [nuts-and-bolts](components/nuts-and-bolts.md) - Contracts + ScrapyardIOException. (`stable`)
* [collections](components/collections.md) - Eager/lazy enumerables, Arr, helpers. (`stable`)
* [conditionable](components/conditionable.md) - `when` / `unless`. (`stable`)
* [macroable](components/macroable.md) - Runtime macros / mixins. (`stable`)
* [reflection](components/reflection.md) - Reflector, ReflectsClosures, `lazy()`. (`stable`)
* [chassis](components/chassis.md) - PSR-11 + ArrayAccess service container. (`stable`)
* [config](components/config.md) - Configuration Repository. (`stable`)
* [filesystem](components/filesystem.md) - Native Filesystem + Flysystem manager/adapters. (`stable`)
* [console](components/console.md) - Workshop CLI (Command, components, scheduling, WorkshopInstance). (`stable`)
* [contracts](components/contracts.md) - Public swap surfaces (`ServiceContainer`, `CLIKernel`, `Events\Dispatcher`, …). (`stable`)
* [core](components/core.md) - Machine, AssemblyLine, ConsoleKernel → Workshop. (`stable`)
* [events](components/events.md) - Sync Dispatcher + defer/NullDispatcher/EventFake; Core owns Event alias / ESP / event:* / Dispatchable. (`stable`)
* [log](components/log.md) - LogManager / Logger (Monolog); Core owns Log alias / LogServiceProvider. Context deferred. (`stable`)
* [cache](components/cache.md) - CacheManager; public stores file+redis; Core owns Cache alias / CacheServiceProvider. (`stable`)
* [redis](components/redis.md) - RedisManager; phpredis/predis; Core owns Redis alias / RedisServiceProvider. (`draft`)
* [encryption](components/encryption.md) - Encrypter encrypt/decrypt; Core owns Crypt + EncryptionServiceProvider. (`stable`)
* [translation](components/translation.md) - Translator + lang files; Core Lang + TranslationServiceProvider. (`stable`)
* [validation](components/validation.md) - Validator factory + rules; Core Validator + ValidationServiceProvider. (`stable`)
* [process](components/process.md) - Process Factory / PendingProcess / Pool over Symfony Process; Core owns Process alias / ProcessServiceProvider. (`stable`)
* [pipeline](components/pipeline.md) - Onion pipes + Hub; Core owns Pipeline MagicAlias + PipelineServiceProvider. (`stable`)
* [concurrency](components/concurrency.md) - ConcurrencyManager sync/process/fork/fiber/pokio drivers; Workshop invoke-serialized-closure; Core owns Concurrency alias / ConcurrencyServiceProvider. (`stable`)
* [sketches](components/sketches.md) - Runner entry, Pipeline middleware, Flow orchestration, AsyncNode via Concurrency. (`stable`)
* [bus](components/bus.md) - Dispatcher sync/queued commands; Core owns Bus MagicAlias + BusServiceProvider. (`stable`)
* [queue](components/queue.md) - QueueManager; public sync/redis/database (+ deferred/failover/null); Core owns Queue alias / QueueServiceProvider; no AWS. (`stable`)
* [database](components/database.md) - SQL connections/query/schema/migrations; Core owns DB glue. (`stable`)
* [polisher](components/polisher.md) - SQL active-record model layer under Database. (`stable`)
* [graph](components/graph.md) - Optional Neo4j companion (`fabricate/graph`); Graph Polisher on Database; not in DefaultProviders. (`stable`)
* [hashing](components/hashing.md) - HashManager bcrypt/argon; Core owns Hash + HashServiceProvider. (`stable`)
* [json-schema](components/json-schema.md) - Fluent JSON Schema builder; Serializer / Deserializer / fromArray; pure static API. (`stable`)
* [http](components/http.md) - Outbound-only Guzzle client; fluent requests, pools/batches, events, and fakes; Core owns Http glue. (`stable`)

# Conventions

* [Component dependency direction](conventions/dependency-direction.md) - Core may know all; Nab → moons + contracts; Broadcasting↔Filesystem OK for `.env` writes. (`stable`)
* [MagicAlias and provider ownership](conventions/magic-aliases.md) - Domains stay pure; Core owns concrete aliases + service providers. (`stable`)
* [AWS is not first-class](conventions/aws-not-first-class.md) - Local/edge only; no public SQS/Dynamo/S3 surface. (`stable`)
* [Fabricate / NutsAndBolts namespace](conventions/namespace-fabricate-nuts-and-bolts.md) - Root PSR-4 maps.
* [Composer replace](conventions/composer-replace.md) - Current `fabricate/*` replaces (grows as components return).
* [Component packages](conventions/component-packages.md) - Split `fabricate/*` vs umbrella.

# Traps

* [env() outside config](traps/env-outside-config.md) - `env()` in config files only; `config()` at runtime.
* [Component composer drift](traps/component-composer-drift.md) - **deprecated** end-state story.
* [Do not copy 0.6 OKF](traps/do-not-copy-0.6-okf.md) - **deprecated**; 0.6 is a restore donor.

# Playbooks

* [Require the framework](playbooks/require-framework.md)
* [Use collect / data helpers](playbooks/use-collect-helpers.md)
* [Port from 0.6.x or Illuminate](playbooks/port-from-0.6-or-illuminate.md) - OKF + Guide/SDK required with every bring-back.
