---
type: Trap
title: Do not copy 0.6 OKF
description: Agents must not assume Machine, Chassis, Workshop, circuits, sensors, or MagicAlias exist because a 0.6 OKF or README says so.
tags: [trap, 0.6, honesty]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T20:52:00Z" }
status: deprecated
sources:
  - id: tree
    resource: src/Fabricate/
    title: On-disk Fabricate contents
  - id: composer
    resource: composer.json
    title: Slim 0.7 replace/require
  - id: readme
    resource: README.md
    title: Vision marketing still present
---

> **Deprecated:** 0.6 kitchen-sink is a restore **donor**. Do not use this trap to block bring-back.

# Trap

Copying concepts from a **0.6.x** framework OKF (or trusting the README’s full-stack story) into work against this **0.7.0** path invents APIs that are not here.[^tree][^composer][^readme]

# Forbidden assumptions (for this tree)

Do not claim these exist under this package path unless you re-verify files:

- `Machine`, `Chassis`, `AssemblyLine`, `Workshop`
- MagicAlias / facades container proxies
- Sketches, circuits, sensors, displays, actuation, rendering modules
- Large `fabricate/*` replace lists beyond the five components

# Safe use of 0.6 materials

- OKF **format** (sections, frontmatter, relative links, log style): yes, as a template
- OKF **claims** about runtime types: no — re-derive from this tree

# Related

- [Relation to 0.6](../orientation/relation-to-0.6.md)
- [Package (0.7)](../orientation/package.md)

[^tree]: On-disk Fabricate contents
[^composer]: Slim 0.7 replace/require
[^readme]: Vision marketing still present
