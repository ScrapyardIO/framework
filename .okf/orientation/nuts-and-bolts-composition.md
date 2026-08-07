---
type: Orientation
title: NutsAndBolts namespace packaging
description: How several Fabricate components currently share Fabricate\NutsAndBolts\ via multi-path PSR-4 and Composer replace.
resource: composer.json
tags: [orientation, composition, components, psr-4, nuts-and-bolts]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T21:08:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: composer
    resource: composer.json
    title: Root autoload and replace
  - id: tree
    resource: src/Fabricate/
    title: Component directories under Fabricate
---

# Model

```
scrapyard-io/framework (umbrella)
├── replace: fabricate/{collections,conditionable,macroable,nuts-and-bolts,reflection}
├── PSR-4 Fabricate\              → src/Fabricate/
├── PSR-4 Fabricate\NutsAndBolts\ → [
│       Macroable/, Collections/, Conditionable/, Reflection/
│   ]
└── files autoload:
        Collections/Helpers/helpers.php
        Collections/Helpers/functions.php
```

[^composer]

# Components involved

| Component | Path | Notes |
|-----------|------|-------|
| nuts-and-bolts | `src/Fabricate/NutsAndBolts/` | Contracts + exception; covered by `Fabricate\` mapping |
| collections | `src/Fabricate/Collections/` | In multi-path `Fabricate\NutsAndBolts\` |
| conditionable | `src/Fabricate/Conditionable/` | Same |
| macroable | `src/Fabricate/Macroable/` | Same |
| reflection | `src/Fabricate/Reflection/` | Same |

**Important:** Shared PHP namespace ≠ single product. NutsAndBolts and its satellites form a **support clique** (deps among them OK; none may know Core). See [dependency direction](../conventions/dependency-direction.md). Future components (Config, Chassis, …) will often use other `Fabricate\…` namespaces.[^tree]

# Why multi-path PSR-4

Each split package’s `composer.json` maps `Fabricate\NutsAndBolts\` → its own root. The umbrella merges four directories into one prefix so `Fabricate\NutsAndBolts\Collection` resolves the same way under the umbrella or a split install.[^composer]

# Placement

OKF lives at the **framework package root** only — never under `src/Fabricate/*`.

# Related

- [Component packages](../conventions/component-packages.md)
- [Namespace convention](../conventions/namespace-fabricate-nuts-and-bolts.md)
- [Composer replace](../conventions/composer-replace.md)

[^composer]: Root autoload and replace
[^tree]: Component directories under Fabricate
