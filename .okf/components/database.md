---
type: Module
title: database
description: fabricate/database SQL connections, query builder, schema builder, migrations, and Core-owned bindings.
resource: src/Fabricate/Database/
tags: [component, database, sql, sqlite, mysql, postgresql, draft]
generated: { by: cursor-agent/gpt-5.6-sol, at: "2026-08-07T06:55:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: manager
    resource: src/Fabricate/Database/DatabaseManager.php
    title: DatabaseManager
  - id: provider
    resource: src/Fabricate/Core/Providers/DatabaseServiceProvider.php
    title: Core database provider
  - id: config
    resource: config/database.php
    title: Database configuration
  - id: package
    resource: src/Fabricate/Database/composer.json
    title: fabricate/database package
---

# Identity

`fabricate/database` provides PDO-backed SQLite, MySQL/MariaDB, and PostgreSQL connections, query/schema builders, migrations, and transactions. SQL Server is intentionally absent.

# Composition

The domain tree contains database behavior only. Core owns `DatabaseServiceProvider`, `MigrationServiceProvider`, and the `DB` / `Schema` MagicAliases. The deferred database provider binds `db.factory`, `db`, `db.connection`, `db.schema`, and `db.transactions`; Migration SP binds `migrator`, `migration.repository`, `migration.creator`.

Workshop commands: `migrate`, `migrate:rollback`, `migrate:status`, `migrate:install`, `make:migration`, `db:seed`, `make:seeder`.

`config/database.php` defaults to SQLite. Runtime code reads `config()`; environment access stays in configuration.

# Verification

`tests/Database/DatabaseTest.php` — SQLite query builder smoke. `tests/Database/MigrateAndSeedTest.php` — migrate / rollback / seed on sqlite.
