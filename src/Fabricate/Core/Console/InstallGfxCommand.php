<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\Core\Console\Concerns\InteractsWithComposerPackages;
use Fabricate\Core\Enums\GfxInstallTarget;
use Fabricate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

use function Fabricate\Console\Prompts\disabled_multiselect;
use function Laravel\Prompts\select;

#[AsCommand(name: 'install:gfx')]
class InstallGfxCommand extends Command
{
    use InteractsWithComposerPackages;

    /**
     * The console command signature.
     */
    protected ?string $signature = 'install:gfx
                    {--composer=global : Absolute path to the Composer binary which should be used to install packages}
                    {--force : Overwrite any existing published or display configuration files}
                    {--tubes : Install ScrapyardIO Tubes}
                    {--sdl3 : Install SDL3 GFX}
                    {--glfw : Install GLFW GFX}
                    {--all : Install every available GFX package}
                    {--default= : Default desktop backend to activate (sdl3 or glfw)}';

    /**
     * The console command description.
     */
    protected string $description = 'Install ScrapyardIO graphics packages (Tubes and/or desktop GFX backends)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targets = $this->resolveTargets();

        if ($targets === []) {
            $this->components->warn('No graphics packages were selected.');

            return self::SUCCESS;
        }

        if (! $this->ensureExtensionsAvailable($targets)) {
            return self::FAILURE;
        }

        $packages = array_values(array_filter(array_map(
            fn (GfxInstallTarget $target): ?string => $target->packageConstraint(),
            $targets,
        )));

        if (! $this->requireComposerPackages($this->option('composer'), $packages)) {
            $this->components->error('Unable to install the selected graphics packages.');

            return self::FAILURE;
        }

        foreach ($targets as $target) {
            $provider = $target->serviceProvider();

            if (is_null($provider)) {
                continue;
            }

            if (! $this->publishInstalledProvider($provider)) {
                $this->components->error("Unable to publish configuration for [{$target->label()}].");

                return self::FAILURE;
            }
        }

        $backends = array_values(array_filter(
            $targets,
            fn (GfxInstallTarget $target): bool => $target->isDesktopBackend(),
        ));

        if ($backends !== []) {
            $default = $this->resolveDefaultBackend($backends);

            if (is_null($default)) {
                return self::FAILURE;
            }

            if (! $this->activateDesktopBackend($default)) {
                return self::FAILURE;
            }
        }

        $this->components->info('Graphics scaffolding installed successfully.');

        return self::SUCCESS;
    }

    /**
     * Resolve the packages the user wants to install.
     *
     * @return array<int, GfxInstallTarget>
     */
    protected function resolveTargets(): array
    {
        if ($this->option('all')) {
            return array_values(array_filter(
                GfxInstallTarget::cases(),
                fn (GfxInstallTarget $target): bool => $target->extensionLoaded(),
            ));
        }

        $fromFlags = [];

        if ($this->option('tubes')) {
            $fromFlags[] = GfxInstallTarget::TUBES;
        }

        if ($this->option('sdl3')) {
            $fromFlags[] = GfxInstallTarget::SDL3;
        }

        if ($this->option('glfw')) {
            $fromFlags[] = GfxInstallTarget::GLFW;
        }

        if ($fromFlags !== []) {
            return $fromFlags;
        }

        return $this->promptForTargets();
    }

    /**
     * Interactively select graphics packages, keeping unavailable backends visible but disabled.
     *
     * @return array<int, GfxInstallTarget>
     */
    protected function promptForTargets(): array
    {
        $options = [];
        $disabled = [];

        foreach (GfxInstallTarget::cases() as $target) {
            $label = $target->label();
            $extension = $target->requiredExtension();

            if (! is_null($extension) && ! $target->extensionLoaded()) {
                $options[$target->value] = "{$label} (ext-{$extension} is missing)";
                $disabled[] = $target->value;

                continue;
            }

            $options[$target->value] = $label;
        }

        $selected = disabled_multiselect(
            label: 'Which graphics packages would you like to install?',
            options: $options,
            scroll: 5,
            required: false,
            hint: 'Use the space bar to select options. Unavailable backends cannot be selected.',
            disabled: $disabled,
        );

        return array_values(array_filter(array_map(
            fn (string $value): ?GfxInstallTarget => GfxInstallTarget::tryFrom($value),
            $selected,
        )));
    }

    /**
     * Reject explicit flags that require missing native extensions.
     *
     * @param  array<int, GfxInstallTarget>  $targets
     */
    protected function ensureExtensionsAvailable(array $targets): bool
    {
        foreach ($targets as $target) {
            $extension = $target->requiredExtension();

            if (is_null($extension) || $target->extensionLoaded()) {
                continue;
            }

            $this->components->error(
                "Unable to install [{$target->label()}]: ext-{$extension} is missing."
            );

            return false;
        }

        return true;
    }

    /**
     * Choose the default desktop backend among the selected backends.
     *
     * @param  array<int, GfxInstallTarget>  $backends
     */
    protected function resolveDefaultBackend(array $backends): ?GfxInstallTarget
    {
        if (count($backends) === 1) {
            return $backends[0];
        }

        $defaultOption = $this->option('default');

        if (! is_null($defaultOption) && $defaultOption !== '') {
            $resolved = GfxInstallTarget::tryFrom((string) $defaultOption);

            if (is_null($resolved) || ! $resolved->isDesktopBackend()) {
                $this->components->error('The --default option must be either [sdl3] or [glfw].');

                return null;
            }

            if (! in_array($resolved, $backends, true)) {
                $this->components->error("The --default backend [{$resolved->value}] was not selected for installation.");

                return null;
            }

            return $resolved;
        }

        $choices = [];

        foreach ($backends as $backend) {
            $choices[$backend->value] = $backend->label();
        }

        $selected = select(
            'Which desktop backend should be the default?',
            $choices,
        );

        return GfxInstallTarget::from($selected);
    }

    /**
     * Activate the selected desktop backend in gfx and displays configuration.
     */
    protected function activateDesktopBackend(GfxInstallTarget $backend): bool
    {
        $files = new Filesystem;
        $gfxPath = $this->scrapyard_io->configPath('gfx.php');
        $displaysPath = $this->scrapyard_io->configPath('displays.php');

        if (! $files->exists($gfxPath)) {
            $this->components->error("Missing configuration file [{$gfxPath}].");

            return false;
        }

        if (! $files->exists($displaysPath)) {
            $this->components->error("Missing configuration file [{$displaysPath}].");

            return false;
        }

        $this->updateGfxDefault($files, $gfxPath, $backend);

        if (! $this->updateDisplaysMain($files, $displaysPath, $backend)) {
            return false;
        }

        $this->components->info("Activated [{$backend->label()}] as the default desktop graphics backend.");

        return true;
    }

    /**
     * Update the rendering default in config/gfx.php.
     */
    protected function updateGfxDefault(Filesystem $files, string $path, GfxInstallTarget $backend): void
    {
        $contents = $files->get($path);

        if (preg_match("/('default'\\s*=>\\s*)'[^']*'/", $contents) === 1) {
            $updated = preg_replace(
                "/('default'\\s*=>\\s*)'[^']*'/",
                "$1'{$backend->value}'",
                $contents,
                1,
            );

            $files->put($path, $updated);

            return;
        }

        $this->components->warn("Unable to automatically update rendering default in [{$path}].");
    }

    /**
     * Replace or install the main windowed display configuration.
     */
    protected function updateDisplaysMain(Filesystem $files, string $path, GfxInstallTarget $backend): bool
    {
        $contents = $files->get($path);
        $keys = $backend->windowedDisplayKeys();

        $mainBlock = <<<PHP
    'main' => [
        'type' => 'windowed',
        'driver' => '{$keys['driver']}',
        'renderer' => '{$keys['renderer']}',
        'buffer' => '{$keys['buffer']}',
    ],
PHP;

        if (preg_match("/['\"]main['\"]\\s*=>\\s*\\[/", $contents) === 1) {
            if (! $this->option('force') && ! $this->mainDisplayAlreadyMatches($contents, $backend)) {
                $this->components->warn(
                    'Existing [config/displays.php] main display was left unchanged. Re-run with --force to replace it.'
                );

                return true;
            }

            $updated = preg_replace(
                "/['\"]main['\"]\\s*=>\\s*\\[(?:[^\\[\\]]*(?:\\[[^\\[\\]]*\\][^\\[\\]]*)*)*\\],?/",
                trim($mainBlock),
                $contents,
                1,
            );

            if (is_null($updated) || $updated === $contents) {
                $this->components->error('Unable to automatically update the main display configuration.');

                return false;
            }

            $files->put($path, $updated);

            return true;
        }

        $updated = preg_replace('/return\\s*\\[/', "return [\n".$mainBlock, $contents, 1);

        if (is_null($updated)) {
            $this->components->error('Unable to automatically insert the main display configuration.');

            return false;
        }

        $files->put($path, $updated);

        return true;
    }

    /**
     * Determine whether the main display already matches the requested windowed backend.
     */
    protected function mainDisplayAlreadyMatches(string $contents, GfxInstallTarget $backend): bool
    {
        $keys = $backend->windowedDisplayKeys();

        return str_contains($contents, "'type' => 'windowed'")
            && str_contains($contents, "'driver' => '{$keys['driver']}'")
            && str_contains($contents, "'renderer' => '{$keys['renderer']}'")
            && str_contains($contents, "'buffer' => '{$keys['buffer']}'");
    }
}
