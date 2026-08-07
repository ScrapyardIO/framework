---
type: Playbook
title: Use collect / data helpers
description: Use collect() and data_* helpers autoloaded from Collections when the umbrella package is installed.
tags: [playbook, collections, helpers]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-06T20:52:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: helpers
    resource: src/Fabricate/Collections/Helpers/helpers.php
    title: Global helpers
  - id: functions
    resource: src/Fabricate/Collections/Helpers/functions.php
    title: Namespaced enum_value
  - id: composer
    resource: composer.json
    title: autoload.files
---

# Autoload

Root `composer.json` registers:[^composer]

- `src/Fabricate/Collections/Helpers/helpers.php`
- `src/Fabricate/Collections/Helpers/functions.php`

# Global helpers (helpers.php)

Present behind `function_exists` guards:[^helpers]

| Helper | Purpose |
|--------|---------|
| `collect($value = [])` | Build a `Fabricate\NutsAndBolts\Collection` |
| `data_get` / `data_set` / `data_fill` / `data_has` / `data_forget` | Dot-notation data access |
| `head` / `last` | First / last of array |
| `value` | Resolve value or Closure |
| `when` | Conditional value helper |

# Namespaced helper

`Fabricate\NutsAndBolts\Helpers\enum_value` unwraps backed/unit enums.[^functions]

# Examples

```php
$names = collect(['a', 'b'])->map(fn ($s) => strtoupper($s));

$city = data_get($payload, 'user.address.city', 'unknown');
```

# Related

- [collections](../components/collections.md)

[^helpers]: Global helpers
[^functions]: Namespaced enum_value
[^composer]: autoload.files
