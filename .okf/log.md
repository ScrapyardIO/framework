# Directory Update Log

## 2026-08-04

* **Fix**: Quoted the `generated.at` timestamp in 23 concept files, and both `verified.by` and `verified.at` in [env() outside config](traps/env-outside-config.md). An unquoted ISO-8601 timestamp inside a YAML flow mapping is not parseable (`found unexpected ':' while scanning a plain scalar`), and neither is a plain scalar containing `human:Name`, so frontmatter failed OKF conformance. All 24 blocks now parse. Found while generating the `scrapyard-io/gpio-framework` bundle, which had inherited the pattern from here.
* **Rewrite**: Refocused concepts on the published package surface and companion-package composition model (README + source). Removed out-of-scope trap content; retained [env() outside config](traps/env-outside-config.md). Updated MagicAlias, stack, ownership, actuation, AGENTS.md, and index framing.

## 2026-08-03

* **Update**: Post-validator fixes — MagicAlias, stack layers caveat, relative concept links, 0.6.0 revalidation note.
* **Initialization**: Created OKF v0.2 bundle for `scrapyard-io/framework` covering orientation, core runtime, domain module catalog, conventions, traps, and playbooks.
* **Creation**: Added package-root [AGENTS.md](../AGENTS.md) pointing at `.okf/index.md`.
