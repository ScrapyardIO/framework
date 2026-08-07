---
type: Playbook
title: Port from 0.6.x or Illuminate
description: Every bring-back into framework 0.7 is incomplete until OKF and docs (Guide and/or SDK) are updated.
tags: [playbook, port, okf, docs]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-07T05:15:00Z" }
verified: { by: "human:Angel Gonzalez (projectsaturnstudios)", at: "2026-08-07T07:50:00Z" }
status: stable
sources:
  - id: laravel-docs
    resource: https://laravel.com/docs
    title: Laravel Guide / API equivalents
  - id: docs-site
    resource: scrapyard-io.projectsaturnstudios
    title: Guide + SDK seeders under database/seeders/content/
---

# Rule

Porting code from **0.6.x** (donor) or **Illuminate** into this package is not done when PHP compiles. A port is complete only when:

1. **OKF** for this package is updated (component / convention / trap / playbook + `log.md`; new concepts stay `draft` until Angel verifies).
2. **Laravel docs** were checked for an equivalent **Guide** page and/or **API / SDK** surface.
3. Matching **Guide** and/or **SDK** entries were brought into the docs site (`scrapyard-io.projectsaturnstudios`) and synced (`php artisan docs:sync 0.7.x`).

Skip docs only when Angel explicitly says there is no public surface yet.

# Steps

1. Scope one compartment (one component or one vertical slice). Prefer small specs Angel verifies before expanding.
2. Port / adapt PHP under `Fabricate\` (namespace, contracts, Core owns aliases + providers — see [magic-aliases](../conventions/magic-aliases.md) and [dependency-direction](../conventions/dependency-direction.md)).
3. Add or extend **framework** Pest coverage for the new surface when it is testable without hardware.
4. Update `.okf/` — concept how-it-works, index links, `composer-replace` if a new `fabricate/*` appears, append `.okf/log.md`.
5. Open Laravel docs for the same capability (e.g. Logging, Events, Filesystem). Decide Guide vs SDK (or both):
   - **Guide** → narrative how-to under `database/seeders/content/docs/` (or current Guide tree).
   - **SDK / API Reference** → contracts, keys, aliases under `database/seeders/content/sdk/`.
6. Rewrite for ScrapyardIO vocabulary (`Machine`, `workshop`, Magic Aliases — never document `pw`). Do not invent unrestored domains.
7. Register pages in `DocumentationContentManifest`, run `docs:sync 0.7.x`, update the docs-site OKF if present.

# Verify

- Framework concept exists / is updated; `log.md` has a dated entry.
- Docs site has Guide and/or SDK page(s) for the ported surface, or Angel waived docs in-session.
- `composer test` (framework) still green for the touched area when tests were added.

# Related

- [Package](../orientation/package.md)
- [Composer replace](../conventions/composer-replace.md)
- Docs site: Guide + SDK for `0.7.x`
