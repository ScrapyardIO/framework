---
type: Runbook
title: Releasing
description: A vX.Y.Z tag runs the suite, then splits every component to general-purpose-io and tags each split with the same version.
tags: [release, splits, ci]
generated: { by: claude-opus-5-5/claude-code, at: "2026-10-14T18:00:05Z" }
sources:
  - id: split-sh
    resource: .github/split.sh
    title: the split script
  - id: split-yml
    resource: .github/workflows/split.yml
    title: the split workflow
---

# Flow

Push a `vX.Y.Z` tag to `ScrapyardIO/framework`. `split.yml` calls `tests.yml`; red suite = no split. Green → `.github/split.sh` runs splitsh-lite v1.0.1 on every `src/<Namespace>/<Component>/` holding a `composer.json`, and pushes each split to `github.com/general-purpose-io/<package name after the vendor>`.[^split-sh]

Versions are lockstep: every split gets the monorepo's tag, components changed or not. A patch tag (`0.10.8` → `0.10.9`) bumps every split by the same patch; only a tag you choose moves the minor.

# Branches

The tag must sit at the head of the `X.Y.x` line branch or of `main`, else the run refuses.

| Tag sits on | Monorepo | Splits receive |
| --- | --- | --- |
| `X.Y.x` and `main` | — | `X.Y.x`, `main`, tag |
| `X.Y.x` only (old line, `main` moved on) | — | `X.Y.x`, tag |
| `main`, no `X.Y.x` yet (new minor) | creates `X.Y.x` at the tag | `X.Y.x`, `main`, tag |
| `main`, `X.Y.x` behind it | fast-forwards `X.Y.x` | `X.Y.x`, `main`, tag |
| `main`, `X.Y.x` diverged | refuses | nothing |

Split branches are force-pushed (splitsh output is deterministic; the first run replaced hand-made history). Tags are never forced.

# Manifest checks

Run on every tag, before any push; all problems listed at once:

- root and component `version`, when present, equal the tag.
- `extra.branch-alias.dev-main` is `X.Y.x-dev` in the root and every component.
- every require/require-dev on a sibling component is `^X.Y` or `^X.Y.N`.

So the commit tagged `vX.Y.0` must already carry the new aliases and `^X.Y` sibling constraints. Then every split repo must answer `git ls-remote`; one missing repo → nothing pushed.

# Adding a component

Create `github.com/general-purpose-io/<name>`, add `src/<Namespace>/<Component>/composer.json` named `<vendor>/<name>`, submit to Packagist after the first split. No workflow edit; the split list is the directory listing.

# Secret

`SPLIT_TOKEN`: a classic PAT with `repo` scope from an account with write access to `general-purpose-io`, stored as a repository secret of `ScrapyardIO/framework`.[^split-yml]

[^split-sh]: `.github/split.sh`
[^split-yml]: `.github/workflows/split.yml`
