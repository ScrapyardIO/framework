---
type: Module
title: filesystem
description: fabricate/filesystem — native Filesystem + Flysystem manager/adapters (domain-only; Core owns provider + Storage alias).
resource: src/Fabricate/Filesystem/
tags: [component, filesystem, storage, flysystem]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T03:20:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: composer
    resource: src/Fabricate/Filesystem/composer.json
    title: fabricate/filesystem manifest
  - id: filesystem
    resource: src/Fabricate/Filesystem/Filesystem.php
    title: Native Filesystem
  - id: manager
    resource: src/Fabricate/Filesystem/FilesystemManager.php
    title: FilesystemManager
  - id: adapter
    resource: src/Fabricate/Filesystem/FilesystemAdapter.php
    title: FilesystemAdapter
  - id: local-adapter
    resource: src/Fabricate/Filesystem/LocalFilesystemAdapter.php
    title: LocalFilesystemAdapter
  - id: provider
    resource: src/Fabricate/Core/Providers/FilesystemServiceProvider.php
    title: Core FilesystemServiceProvider
  - id: storage-alias
    resource: src/Fabricate/Core/MagicAliases/Storage.php
    title: Storage MagicAlias
  - id: contract
    resource: src/Fabricate/Contracts/Filesystem/Filesystem.php
    title: Filesystem contract
  - id: root
    resource: composer.json
    title: Umbrella replace
  - id: ownership
    resource: .okf/conventions/magic-aliases.md
    title: MagicAlias / provider ownership
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/filesystem`[^composer] |
| Path | `src/Fabricate/Filesystem/` |
| PHP namespace | `Fabricate\Filesystem\` |
| Contracts | `Fabricate\Contracts\Filesystem\*`[^contract] |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Domain — Core-free; Core binds `files` / `filesystem` + `Storage` alias[^ownership] |

Ported from 0.6 donor with ownership correction: **no** `FilesystemServiceProvider` in this package.

# How it works

## Mental model

Two layers:

1. **Native** `Filesystem` — PHP file ops (exists/get/put/find/…). Used by Core (`PackageManifest`, path helpers).
2. **Manager** `FilesystemManager` — named disks via Flysystem adapters (`local`, `ftp`, `sftp`, `s3`, scoped). Falls back to native `Filesystem` when Flysystem local adapter is missing.

```php
$files = new \Fabricate\Filesystem\Filesystem;
$files->put('/tmp/hello.txt', 'hi');

// Via Core provider + alias (after bootstrap):
Storage::disk('local')->put('hello.txt', 'hi');
```

## API (domain)

| Symbol | Role |
|--------|------|
| `Filesystem` | Native local FS[^filesystem] |
| `FilesystemManager` | Disk factory (`disk`/`cloud`/`extend`) — types `ServiceContainer`[^manager] |
| `FilesystemAdapter` / `LocalFilesystemAdapter` / `AwsS3V3Adapter` | Flysystem wrappers[^adapter][^local-adapter] |
| `LockableFile` | Locked file handle |
| `Enums\Visibility` | `PUBLIC` / `PRIVATE` (string-backed) |
| `join_paths()` | Helper (autoload files) |

## Runtime wiring (Core — not this package)

| Piece | Home |
|-------|------|
| `files` / `filesystem` / `filesystem.disk` / `filesystem.cloud` | `Fabricate\Core\Providers\FilesystemServiceProvider`[^provider] |
| `Storage` MagicAlias | `Fabricate\Core\MagicAliases\Storage`[^storage-alias] |
| Default provider list | `DefaultProviders` includes Core Filesystem provider |
| App config | `config/filesystems.php` (`default`, `cloud`, `disks.*`) |

# Requires

```json
"fabricate/contracts": "^0.7.0",
"fabricate/macroable": "^0.7.0",
"fabricate/collections": "^0.7.0",
"fabricate/conditionable": "^0.7.0",
"symfony/mime": "^7.4 || ^8.0",
"symfony/finder": "^7.4 || ^8.0",
"symfony/filesystem": "^7.4 || ^8.0"
```

Flysystem drivers are **suggested** (not hard-required) so native `Filesystem` stays usable without League packages.[^composer]

# Related

- [config](config.md) — `filesystems.*` config bag
- [contracts](contracts.md) — `Contracts\Filesystem\*`
- [core](core.md) — provider + Storage alias
- [MagicAlias ownership](../conventions/magic-aliases.md)

[^composer]: fabricate/filesystem manifest
[^filesystem]: Native Filesystem
[^manager]: FilesystemManager
[^adapter]: FilesystemAdapter
[^local-adapter]: LocalFilesystemAdapter
[^provider]: Core FilesystemServiceProvider
[^storage-alias]: Storage MagicAlias
[^contract]: Filesystem contract
[^root]: Umbrella replace
[^ownership]: MagicAlias / provider ownership
