<?php

namespace Fabricate\Core\Console\Concerns;

use Fabricate\Filesystem\Filesystem;
use Fabricate\NutsAndBolts\Composer;
use Symfony\Component\Process\Process;

use function Fabricate\NutsAndBolts\Helpers\php_binary;
use function Fabricate\NutsAndBolts\Helpers\workshop_binary;

trait InteractsWithComposerPackages
{
    /**
     * Require Composer packages, skipping those already present in composer.json.
     *
     * @param  array<int, string>  $packages
     */
    protected function requireComposerPackages(string $composer, array $packages): bool
    {
        $packages = array_values(array_filter(
            $packages,
            fn (string $package): bool => ! $this->composerHasPackage($package),
        ));

        if ($packages === []) {
            $this->components->info('Required Composer packages are already installed.');

            return true;
        }

        $composerBinary = $composer === 'global' ? null : $composer;

        return $this->makeComposer()->requirePackages(
            $packages,
            false,
            $this->output,
            $composerBinary,
        );
    }

    /**
     * Determine whether the root composer.json already requires the package.
     */
    protected function composerHasPackage(string $package): bool
    {
        $name = explode(':', $package, 2)[0];

        try {
            return $this->makeComposer()->hasPackage($name);
        } catch (\RuntimeException) {
            return false;
        }
    }

    /**
     * Publish assets for a provider in a freshly booted Workshop process.
     *
     * @param  array<int, string>  $extraArguments
     */
    protected function publishInstalledProvider(string $provider, array $extraArguments = []): bool
    {
        $arguments = [
            'vendor:publish',
            '--provider',
            $provider,
            ...$extraArguments,
        ];

        if ($this->option('force')) {
            $arguments[] = '--force';
        }

        return $this->runWorkshopCommand($arguments) === self::SUCCESS;
    }

    /**
     * Run a Workshop command in a separate process against the application base path.
     *
     * @param  array<int, string>  $arguments
     */
    protected function runWorkshopCommand(array $arguments): int
    {
        $command = [
            $this->phpBinary(),
            $this->workshopBinary(),
            ...$arguments,
        ];

        $process = new Process(
            $command,
            $this->scrapyard_io->basePath(),
            ['COMPOSER_MEMORY_LIMIT' => '-1'],
        );

        $process->setTimeout(null);

        return $process->run(function (string $type, string $output): void {
            $this->output->write($output);
        });
    }

    /**
     * Create a Composer helper bound to the application base path.
     */
    protected function makeComposer(): Composer
    {
        return new Composer(new Filesystem, $this->scrapyard_io->basePath());
    }

    /**
     * Get the path to the appropriate PHP binary.
     */
    protected function phpBinary(): string
    {
        return php_binary();
    }

    /**
     * Get the path to the Workshop binary.
     */
    protected function workshopBinary(): string
    {
        $local = $this->scrapyard_io->basePath('workshop');

        return file_exists($local) ? $local : workshop_binary();
    }
}
