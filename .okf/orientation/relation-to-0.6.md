---
type: Orientation
title: Relation to 0.6
description: 0.6.x was a kitchen-sink app runtime; 0.7.0 on disk is a slim NutsAndBolts umbrella. Do not treat 0.6 OKF concepts as true for this tree.
tags: [orientation, migration, 0.6, 0.7]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T20:52:00Z" }
status: deprecated
sources:
  - id: composer
    resource: composer.json
    title: 0.7.0 version and slim require/replace
  - id: tree
    resource: src/Fabricate/
    title: Only component directories present
  - id: okf06
    resource: README.md
    title: Local README (vision); 0.6 OKF is an external format example only
---

> **Deprecated framing:** 0.6 is a bring-back donor for reconstituting 0.7, not a forbidden tree. Rewrite when restore slice is locked.

# Contrast

| Aspect | Typical 0.6.x framework tree | This 0.7.0 tree |
|--------|------------------------------|-----------------|
| Role | Application runtime + hardware registries | NutsAndBolts foundation umbrella |
| Root `require` | Large Laravel-like / hardware stack | `symfony/polyfill-php86` only[^composer] |
| `replace` | Many `fabricate/*` domains | Five components only[^composer] |
| Core types | Machine, Chassis, Workshop, … | Absent[^tree] |
| Domains | circuits, sensors, displays, … | Absent[^tree] |
| OKF | Documents kitchen-sink surface | Documents components + packaging only |

# How to use the 0.6 OKF

A 0.6 OKF may exist in another checkout. Use it as a **format/style example** (index sections, frontmatter shape, relative links) — **not** as a source of truth for types in this package. Local `README.md` still markets the broader vision; that is not an inventory of classes here.[^readme]

See trap: [Do not copy 0.6 OKF](../traps/do-not-copy-0.6-okf.md).

# Agent rule

If a task mentions Machine, AssemblyLine, sketches, circuits, sensors, MagicAlias, or Workshop **in this package path**, stop and re-check the tree. Those claims belong to 0.6-era docs or other packages, not to 0.7.0 on disk.

[^composer]: 0.7.0 version and slim require/replace
[^tree]: Only component directories present
[^readme]: Local README (vision); 0.6 OKF is an external format example only
