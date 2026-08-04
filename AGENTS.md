# Agent guidelines — scrapyard-io/framework

## Knowledge Bundle (OKF)

This package has an Open Knowledge Format bundle at [`.okf/`](.okf/).

Before changing framework code or advising on ScrapyardIO architecture:

1. Read [`.okf/index.md`](.okf/index.md) first (progressive disclosure).
2. Open only the linked concepts needed for the task.
3. Prefer `status: stable` concepts; treat `deprecated` as historical only.
4. When you learn something durable, update the affected `.okf` concept(s) and append `.okf/log.md`. New/changed concepts stay `status: draft` until a human verifies them.
5. Do **not** create `.okf` folders under `src/Fabricate/*` components — knowledge for this package lives at the package root only.
6. Chip drivers, native bindings, and device-specific patterns belong in sibling package `.okf` bundles (microscrap, DOSR, gpio-framework, waveforms, tubes). Neo4j remains cross-project memory.

## Hard rules (quick)

- Namespace is `Fabricate\`.
- Protocol adapters and device drivers are **not** this package — compose them from companions.
- Prefer verifying the unfinished carrier/driver before rewriting higher-level device packages.
