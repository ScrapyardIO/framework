---
type: Convention
title: Composer replace
description: scrapyard-io/framework replaces fabricate/* component packages at self.version (grows as components return).
resource: composer.json
tags: [convention, composer, replace]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T20:52:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: composer
    resource: composer.json
    title: replace map
---

# Rule

Root `composer.json` `replace`:[^composer]

| Package | Version |
|---------|---------|
| `fabricate/bus` | `self.version` |
| `fabricate/cache` | `self.version` |
| `fabricate/chassis` | `self.version` |
| `fabricate/config` | `self.version` |
| `fabricate/console` | `self.version` |
| `fabricate/contracts` | `self.version` |
| `fabricate/database` | `self.version` |
| `fabricate/encryption` | `self.version` |
| `fabricate/hashing` | `self.version` |
| `fabricate/graph` | `self.version` |
| `fabricate/http` | `self.version` |
| `fabricate/events` | `self.version` |
| `fabricate/filesystem` | `self.version` |
| `fabricate/json-schema` | `self.version` |
| `fabricate/log` | `self.version` |
| `fabricate/redis` | `self.version` |
| `fabricate/collections` | `self.version` |
| `fabricate/conditionable` | `self.version` |
| `fabricate/concurrency` | `self.version` |
| `fabricate/sketches` | `self.version` |
| `fabricate/macroable` | `self.version` |
| `fabricate/magic-aliases` | `self.version` |
| `fabricate/nuts-and-bolts` | `self.version` |
| `fabricate/pagination` | `self.version` |
| `fabricate/pipeline` | `self.version` |
| `fabricate/process` | `self.version` |
| `fabricate/queue` | `self.version` |
| `fabricate/reflection` | `self.version` |
| `fabricate/translation` | `self.version` |
| `fabricate/validation` | `self.version` |

Applications should:

```bash
composer require scrapyard-io/framework
```

Do **not** require the split `fabricate/*` components separately when depending on this umbrella — they are replaced at `self.version` (currently `0.7.0`).

# Related

- [Component packages](component-packages.md)
- [Require the framework](../playbooks/require-framework.md)

[^composer]: replace map
