<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\Core\Console\Concerns\InteractsWithComposerPackages;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'install:sensors')]
class InstallSensorsCommand extends Command
{
    use InteractsWithComposerPackages;

    /**
     * The console command signature.
     */
    protected ?string $signature = 'install:sensors
                    {--composer=global : Absolute path to the Composer binary which should be used to install packages}
                    {--force : Overwrite any existing published files}';

    /**
     * The console command description.
     */
    protected string $description = 'Install ScrapyardIO Waveforms sensor scaffolding';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->requireComposerPackages($this->option('composer'), [
            'scrapyard-io/waveforms:^0.6.0',
        ])) {
            $this->components->error('Unable to install [scrapyard-io/waveforms].');

            return self::FAILURE;
        }

        if (! $this->publishInstalledProvider('ScrapyardIO\\Waveforms\\Core\\Providers\\WaveformsServiceProvider')) {
            $this->components->error('Unable to publish Waveforms configuration.');

            return self::FAILURE;
        }

        $this->components->info('Waveforms sensor scaffolding installed successfully.');

        return self::SUCCESS;
    }
}
