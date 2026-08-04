---
type: Convention
title: Fabricate namespace
description: All framework code PSR-4 maps Fabricate\ → src/Fabricate/; apps use App\ and compose scrapyard-io/framework.
tags: [convention, namespace]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: psr-4 Fabricate\\ mapping
---

# Rule

```text
Fabricate\  →  src/Fabricate/
```

NutsAndBolts PSR-4 also merges Macroable, Collections, Conditionable, Reflection directories under `Fabricate\NutsAndBolts\`.[^composer]

Legacy names (BareMetal / RealityInterface as *app runtime*) are not the current framework identity. Device packages may still use BareMetal bases — that is DOSR-layer vocabulary, not “the framework is BareMetal”.

[^composer]: psr-4 Fabricate\\ mapping
