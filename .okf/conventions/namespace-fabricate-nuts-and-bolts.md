---
type: Convention
title: Fabricate / NutsAndBolts namespace
description: Root PSR-4 maps Fabricate\ to src/Fabricate/ and Fabricate\NutsAndBolts\ to the four component directories (multi-path).
resource: composer.json
tags: [convention, namespace, psr-4]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T20:52:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: composer
    resource: composer.json
    title: Root psr-4 autoload
---

# Rule

From root `composer.json`:[^composer]

```json
"psr-4": {
    "Fabricate\\": "src/Fabricate/",
    "Fabricate\\NutsAndBolts\\": [
        "src/Fabricate/Macroable/",
        "src/Fabricate/Collections/",
        "src/Fabricate/Conditionable/",
        "src/Fabricate/Reflection/"
    ]
}
```

# Consequences

- Prefer importing these types as `Fabricate\NutsAndBolts\...` (e.g. `Collection`, `Concerns\Macroable`).
- Types under `src/Fabricate/NutsAndBolts/` (contracts, `ScrapyardIOException`) also live in `Fabricate\NutsAndBolts\` via the broader `Fabricate\` mapping.
- Do not invent a separate top-level namespace per component folder name (`Fabricate\Collections\` is not how these classes are declared).

# Related

- [NutsAndBolts composition](../orientation/nuts-and-bolts-composition.md)
- [Component packages](component-packages.md)

[^composer]: Root psr-4 autoload
