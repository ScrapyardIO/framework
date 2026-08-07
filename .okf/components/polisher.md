---
type: Module
title: polisher
description: Polisher is ScrapyardIO's SQL active-record model layer, ported from Illuminate Eloquent under Fabricate Database.
resource: src/Fabricate/Database/Polisher/
tags: [component, database, polisher, orm, models, draft]
generated: { by: cursor-agent/gpt-5.6-sol, at: "2026-08-07T06:55:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: model
    resource: src/Fabricate/Database/Polisher/Model.php
    title: Polisher Model
  - id: test
    resource: tests/Database/PolisherTest.php
    title: SQLite CRUD verification
---

# Purpose

Polisher is the renamed SQL model layer under `Fabricate\Database\Polisher`. It keeps model CRUD, casts, scopes, relations, factories, collections, and queue serialization without exposing the Illuminate Eloquent namespace.

Workshop: `make:model` (optional `--migration`). Guide docs live under the **Polisher ORM** nav group (with Graph).

Neo4j graph models are a separate companion (`fabricate/graph` → `Fabricate\Graph\Polisher\Model`); they are not merged into SQL Polisher grammars. See [graph](graph.md).

# Runtime wiring

Core's database provider sets the Polisher connection resolver and event dispatcher. Queue model serialization points at Polisher models and collections.

Optional broadcasting, routing, and JSON-resource surfaces are retained behind minimal Fabricate contracts/classes so model methods remain available while Auth, Session, and a complete HTTP/Broadcasting stack remain out of scope.

# Verification

`tests/Database/PolisherTest.php` performs create, update, find, and delete against SQLite `:memory:`.
