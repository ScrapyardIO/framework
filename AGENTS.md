# Agent guidelines — scrapyard-io/framework

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from Composer dist via `.gitattributes` `export-ignore`).

Before changing framework code or advising on ScrapyardIO architecture:

1. Read [`.okf/index.md`](.okf/index.md) first (progressive disclosure).
2. Open only the linked concepts needed for the task.
3. Prefer `status: stable` concepts; treat `deprecated` as historical only.
4. When you learn something durable about **this package**, update the affected `.okf` concept(s) and append `.okf/log.md`. New/changed concepts stay `status: draft` until a human verifies them.
5. Do **not** create `.okf` folders under `src/Fabricate/*` — knowledge for this package lives at the package root only.
6. Keep concepts scoped to this package’s public surface and composition model. Chip drivers, native bindings, and device-specific patterns belong in sibling packages’ own docs / `.okf` bundles.

## Package rules (quick)

- Namespace is `Fabricate\`.
- Protocol adapters and device drivers are **not** this package — compose them from companions (`gpio-framework`, `dept-of-scrapyard-robotics/*`, `microscrap/*`, waveforms, tubes).
- Runtime configuration: `env()` in config files only; use `config()` at runtime (see `.okf/traps/env-outside-config.md`).
