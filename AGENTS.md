# Agent guidelines — scrapyard-io/framework

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s) and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them.

Do **not** create `.okf` folders under `src/GeneralPurposeIO/*` — knowledge for this package lives at the package root only.

## Where this package sits

`ext-posi` / `ext-ftdi` (1:1 syscalls) → `microscrap/*` (libgpiod / libmpsse / spidev / termios in PHP) → **`scrapyard-io/framework`** (protocol managers, buses, Circuits, the `gpio` dock resource) → `dept-of-scrapyard-robotics/*` (chip drivers) → `venusian/surface` EmbeddedPanels.

First-class module beside Surface. Same house rules.

## Package rules (quick) — 0.8.x

- Composer: `scrapyard-io/framework` **0.8.0**. PHP `^8.4|^8.5|^8.6`. Requires `venusian/framework ^0.8.0`. Namespace `GeneralPurposeIO\` → `src/GeneralPurposeIO/`.
- **Split packages.** `gpio/analog`, `gpio/circuits`, `gpio/common`, `gpio/contracts`, `gpio/digital`, `gpio/i2c`, `gpio/pwm`, `gpio/spi`, `gpio/uart` — each with `composer.json`, `.gitattributes`, `LICENSE` under `src/GeneralPurposeIO/*`, each in the root `replace` map. `Core` is not split: it holds the aggregate provider, the `GPIO` / `Circuit` aliases and the dock resource, and ships only with the umbrella.
- **Dependency direction.** Contracts import `Voyager\Contracts`, the concrete `Voyager\IOPools\Presumption`, and microscrap DTOs. Components import Contracts, Common, NutsAndBolts, MagicAliases, microscrap. Nothing below Core imports Core. Protocol providers reach the `gpio` manager through `$this->app->bound('gpio')`, never through the alias class.
- **Microscrap is `suggest`, never `require`** on a component. A posix-only install must not drag `ext-ftdi`.
- **Exceptions** descend from `GeneralPurposeIO\Contracts\Core\GPIOLevelException`.
- **Framework calls microscrap helpers only** (`gpiod_*`, `i2c_*`, `spi_*`, `uart_*`, `mpsse_*`, `posix_*`). Never `Posi\System` or `Ftdi\FTDI`.
- **Dock.** One `gpio` resource (`Core\IOPools\GPIOResourceDriver`), registered at provider boot when `config('gpio.io_pools.enabled')`. `tick()` never waits. ICs opt in per call (`watch`, `receive`, `defer`); blocking IO stays as it is. See `.okf/dock-resource.md`.
- Discovery: `extra.venusian.providers` → `ScrapyardIOServiceProvider`; aliases `GPIO`, `Circuit`.
- Enums int- or string-backed, FULLY UPPERCASE cases. No class constants. `is_null($x)` over `$x === null`.
- Chip drivers do not live here. Analog is scaffold; PWM ships `enabled => false`.

## Verification

```bash
vendor/bin/pest            # Mac: fakes only; hardware tests skip
php -l <file>
```

Mac `composer update` needs `--ignore-platform-req=ext-posi --ignore-platform-req=ext-ftdi` — the installed binaries report `0.5.0` / `0.7.0` while every manifest here requires `^0.8.0`; rebuild the exts at 0.8.0 to clear it for real.

Before any bulk copy into this tree, inventory the destination (`git ls-tree`) — the scaffold's LICENSE / composer.json / .gitattributes per component are hand-written; never overwrite or shadow them.

Protocol drivers are proven on the Pi 5 over `fnk` (SSD1306 at 0x3C, FNK0107 at 0x21 on i2c-1). A skipped test is not evidence.
