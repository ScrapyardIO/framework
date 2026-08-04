---
type: CoreType
title: Providers and package discovery
description: Service providers register bindings; Composer scrapyard-io metadata + workshop package:discover builds the package manifest.
tags: [core, providers, discovery]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Extending the Framework / package:discover
  - id: composer-scripts
    resource: src/Fabricate/Core/ComposerScripts.php
    title: postAutoloadDump hook
---

# Service providers

Extend `Fabricate\NutsAndBolts\ServiceProvider` (or framework equivalents). Register in `bootstrap/providers.php` or via package discovery.[^readme]

# Discovery

Packages advertise providers through Composer metadata. After `composer dump-autoload`, `Fabricate\Core\ComposerScripts::postAutoloadDump` runs and apps typically call:

```bash
php workshop package:discover
```

Skeleton `composer.json` should include the post-autoload-dump script calling ComposerScripts + `package:discover`.[^readme]

# Related

- [Add a service provider](../playbooks/add-service-provider.md)
- [Workshop](workshop.md)

[^readme]: Extending the Framework / package:discover
[^composer-scripts]: postAutoloadDump hook
