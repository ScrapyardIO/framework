---
type: Module
title: json-schema
description: fabricate/json-schema — fluent JSON Schema builder (Type objects, Serializer, Deserializer, fromArray); pure domain, static API only.
resource: src/Fabricate/JsonSchema/
tags: [component, json-schema, draft]
generated: { by: cursor-agent/composer-2.5, at: "2026-08-07T06:00:00Z" }
verified: { by: null, at: null }
status: stable
sources:
  - id: json-schema
    resource: src/Fabricate/JsonSchema/JsonSchema.php
    title: JsonSchema static API
  - id: factory
    resource: src/Fabricate/JsonSchema/JsonSchemaTypeFactory.php
    title: JsonSchemaTypeFactory
  - id: serializer
    resource: src/Fabricate/JsonSchema/Serializer.php
    title: Serializer
  - id: deserializer
    resource: src/Fabricate/JsonSchema/Deserializer.php
    title: Deserializer + fromArray
  - id: contract
    resource: src/Fabricate/Contracts/JsonSchema/JsonSchema.php
    title: JsonSchema contract
  - id: composer
    resource: src/Fabricate/JsonSchema/composer.json
    title: fabricate/json-schema package
  - id: root
    resource: composer.json
    title: Umbrella replace
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `fabricate/json-schema`[^composer][^root] |
| Path | `src/Fabricate/JsonSchema/` |
| PHP namespace | `Fabricate\JsonSchema\` |
| Contracts | `Fabricate\Contracts\JsonSchema\JsonSchema` |
| Package files | `composer.json`, `LICENSE.md`, `.gitattributes` |
| Umbrella | `replace` → `self.version`[^root] |
| Layer role | Pure domain — **no** Core service provider, **no** MagicAlias |

# How it works

1. **Build** — `Fabricate\JsonSchema\JsonSchema` exposes a static fluent API (`object`, `array`, `string`, `integer`, `number`, `boolean`, `union`, `anyOf`) via `JsonSchemaTypeFactory`. Each call returns a `Types\Type` subclass with chainable constraints (`required`, `nullable`, `min`/`max`, `enum`, …).
2. **Serialize** — `$type->toArray()` (or `Serializer::serialize`) emits a JSON Schema subset array suitable for structured payloads (AI tool schemas, config validation shapes, etc.).
3. **Deserialize** — `JsonSchema::fromArray($schema)` delegates to `Deserializer`, which rebuilds `Type` objects from arrays. Supports local `$ref`, nullable unions, `anyOf` compositions, and numeric/string/array/object constraints within the supported subset.
4. **Contract** — `Fabricate\Contracts\JsonSchema\JsonSchema` is implemented by `JsonSchemaTypeFactory` for injection where a factory instance is preferred over static calls.
5. **Enums** — `SupportedUnionType` (string-backed) replaces class constants for union member types; `DeserializerLimit::MAX_NODES` caps deserialization depth.

Pest: `tests/JsonSchema/JsonSchemaTest.php` — object schema `toArray` + `fromArray` round-trip (plus `anyOf` / `union`).

Docs: Guide `docs/json-schema.md` + SDK `sdk/json-schema.md` on the docs site.

[^composer]: fabricate/json-schema package
[^root]: Umbrella replace
