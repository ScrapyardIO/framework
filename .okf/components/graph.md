---
type: Component
title: Graph (Neo4j companion)
description: Optional Neo4j driver and Graph Polisher models (fabricate/graph); not in DefaultProviders; adapts eloquent4j onto Database + Polisher.
tags: [component, graph, neo4j, polisher, companion, draft]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T08:30:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: connection
    resource: src/Fabricate/Graph/Database/Neo4jConnection.php
    title: Neo4jConnection
  - id: connector
    resource: src/Fabricate/Graph/Database/Connectors/Neo4jConnector.php
    title: Neo4jConnector
  - id: model
    resource: src/Fabricate/Graph/Polisher/Model.php
    title: Graph Polisher Model
  - id: provider
    resource: src/Fabricate/Graph/GraphServiceProvider.php
    title: Optional GraphServiceProvider
  - id: helpers
    resource: src/Fabricate/Graph/Helpers/functions.php
    title: cypher helpers
  - id: composer
    resource: src/Fabricate/Graph/composer.json
    title: fabricate/graph package
  - id: database
    resource: .okf/components/database.md
    title: SQL Database
  - id: polisher
    resource: .okf/components/polisher.md
    title: SQL Polisher
---

# Scope

`fabricate/graph` is a **companion** Neo4j package for ScrapyardIO 0.7. It is **not** merged into SQL Polisher grammars and is **not** registered in Core `DefaultProviders`. Guide docs sit under **Polisher ORM** beside SQL Polisher. There is **no** `graph:migrate` — Cypher schema changes use normal `workshop migrate` migrations. Workshop lists `make:graph-model` when the Graph package is present.

Apps opt in by:

1. Requiring `laudis/neo4j-php-client` (umbrella `suggest` / graph package `require`)
2. Registering `Fabricate\Graph\GraphServiceProvider`
3. Adding `database.connections.neo4j` (see `src/Fabricate/Graph/config/neo4j.php`)

**Excluded:** Auth, Session, inbound HTTP middleware, RBAC, data-masking tied to Auth (from private `eloquent4j` donor).

# How it works

- `Neo4jConnector` builds a `laudis/neo4j-php-client` client from config (`bolt` / `bolt+s` / `neo4j` schemes).
- `GraphServiceProvider` binds `db.connector.neo4j` and `$db->extend('neo4j', …)` → `Neo4jConnection`.
- `Fabricate\Graph\Polisher\Model` extends SQL `Fabricate\Database\Polisher\Model`; `$table` is the Neo4j label; default connection name `neo4j`.
- Helpers: `cypher()`, `cypher_one()`, `cypher_run()`, `neo4j_connection()`.

# Packaging

| Field | Value |
|-------|-------|
| Composer | `fabricate/graph` |
| Path | `src/Fabricate/Graph/` |
| Umbrella | `replace` → `self.version`; Neo4j client is **suggested**, not hard-required by the umbrella |

# Verification

`tests/Graph/GraphPackageTest.php` — class/provider/extension smoke (no live Neo4j required in CI).
