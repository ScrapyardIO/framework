---
type: Convention
title: Composer replace
description: scrapyard-io/framework replaces individual fabricate/* component packages at self.version — require the framework, not splinter fabricate packages.
tags: [convention, composer]
generated: { by: cursor-agent/grok-4.5, at: 2026-08-04T03:55:00Z }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: replace map for fabricate/*
---

# Rule

This package’s `composer.json` `replace` lists many `fabricate/*` components (`actuation`, `cache`, `console`, `sketches`, …) at `self.version`.[^composer]

Applications and agents should:

```bash
composer require scrapyard-io/framework
```

Do **not** try to require split `fabricate/console` etc. as separate shipped packages from this monorepo layout — they are replaced by the umbrella.

[^composer]: replace map for fabricate/*
