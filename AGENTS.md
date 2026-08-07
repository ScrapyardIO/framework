# Agent guidelines — scrapyard-io/framework

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from Composer dist via `.gitattributes` `export-ignore`).

Before changing framework code or advising on ScrapyardIO architecture **for this package**:

1. Read [`.okf/index.md`](.okf/index.md) first (progressive disclosure).
2. Open only the linked concepts needed for the task.
3. Prefer `status: stable` concepts; treat `deprecated` as historical only. Concepts in this bundle are human-verified `stable` unless marked `deprecated`.
4. When you learn something durable about **this package**, update the affected `.okf` concept(s) and append `.okf/log.md`. New/changed concepts stay `status: draft` until a human verifies them.
5. Do **not** create `.okf` folders under `src/Fabricate/*` (component folders) — knowledge for this package lives at the package root only.
6. Keep chip drivers, GPIO transport, and native bindings out of this package’s OKF — those belong in sibling packages. Framework runtime/components (Config, Chassis, Core/Machine, domain modules, etc.) **are** in scope as they are restored into this tree.

## Package rules (quick) — 0.7.x (reconstituting)

- Composer: `scrapyard-io/framework` **0.7.0**. PHP `^8.4|^8.5|^8.6`.
- Namespace root is `Fabricate\`. Components live under `src/Fabricate/*`.
- **Dependency direction** (see `.okf/conventions/dependency-direction.md`):
  - **Core** may be aware of everything.
  - **Nothing below Core** depends on Core (`app()`, Machine, Workshop, paths, …).
  - **NutsAndBolts** may depend on its moons (Collections, Conditionable, Macroable, Reflection, …) **and `fabricate/contracts`** — not Filesystem, Broadcasting, Chassis, or other components.
  - **Other components** prefer NutsAndBolts; peer deps OK when justified (e.g. Broadcasting ↔ Filesystem for `.env` install writes); never Core.
- Nab `Env` is read-side; `.env` mutation with Filesystem belongs on Broadcasting (websockets) / Core commands, not Nab.
- **MagicAliases / providers**: domain packages stay pure (no aliases/providers, no Chassis/Core). **Core** owns concrete MagicAliases and domain service providers. See `.okf/conventions/magic-aliases.md`.
- Shared PHP namespace `Fabricate\NutsAndBolts\` for some moons is packaging — not ownership of those components by the NutsAndBolts folder.
- This line is reconstituting toward a full framework surface. Do **not** treat current hollowness as the product end-state.
- The 0.6.x kitchen-sink tree in the workspace is a **donor / reference** for bring-back — not forbidden knowledge.
- Protocol adapters and device drivers remain companion packages (`gpio-framework`, `dept-of-scrapyard-robotics/*`, `microscrap/*`, waveforms, tubes).
- Runtime configuration: `env()` in config files only; use `config()` at runtime once those helpers exist (see `.okf/traps/env-outside-config.md`).
