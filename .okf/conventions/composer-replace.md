---
type: Convention
title: Composer replace
description: scrapyard-io/framework replaces individual fabricate/* component packages at self.version — require the framework, not splinter fabricate packages.
tags: [convention, composer]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-04T03:55:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: replace map for fabricate/*
---

# Rule

This package’s `composer.json` `replace` lists many `fabricate/*` components (`actuation`, `cache`, `console`, `sketches`, …) at `self.version`.[^composer]

Applications should:

```bash
composer require scrapyard-io/framework
```

Do **not** require split `fabricate/*` component packages separately — they are replaced by this umbrella package at `self.version`.

[^composer]: replace map for fabricate/*
