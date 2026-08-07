---
type: Playbook
title: Require the framework
description: Install scrapyard-io/framework and import Fabricate\NutsAndBolts\* types from the umbrella.
tags: [playbook, composer]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T20:52:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: composer
    resource: composer.json
    title: Package name, replace, autoload
---

# Steps

1. In the consuming app:

```bash
composer require scrapyard-io/framework
```

2. Rely on the umbrella `replace` for the five components — do not also require `fabricate/collections` etc. separately.[^composer]

3. Import types from `Fabricate\NutsAndBolts\`:

```php
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Concerns\Macroable;
use Fabricate\NutsAndBolts\Concerns\Conditionable;
use Fabricate\NutsAndBolts\Contracts\Arrayable;
```

4. Expect global helpers from Collections (`collect`, `data_get`, …) via root `autoload.files`. Do **not** assume Reflection’s `lazy()` is registered unless that helper file is loaded (see [reflection](../components/reflection.md)).

# Verify

- `composer show scrapyard-io/framework` reports `0.7.0` (or your locked version).
- Class exists: `Fabricate\NutsAndBolts\Collection`.

# Related

- [Composer replace](../conventions/composer-replace.md)
- [Use collect helpers](use-collect-helpers.md)

[^composer]: Package name, replace, autoload
