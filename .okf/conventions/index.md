# Conventions

* [Component dependency direction](dependency-direction.md) - Core may know all; Nab moons-only; Broadcasting↔Filesystem OK for `.env` writes. (`stable`)
* [MagicAlias and provider ownership](magic-aliases.md) - Domains stay pure; Core owns concrete aliases + service providers. (`stable`)
* [Fabricate / NutsAndBolts namespace](namespace-fabricate-nuts-and-bolts.md) - `Fabricate\` + multi-path `Fabricate\NutsAndBolts\`.
* [Composer replace](composer-replace.md) - Five `fabricate/*` replaces at `self.version`.
* [Component packages](component-packages.md) - Each component shippable as `fabricate/*` but replaced by the umbrella.
