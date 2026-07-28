<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\Core\Console\Concerns\InteractsWithComposerPackages;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'install:gpio')]
class InstallGpioCommand extends Command
{
    use InteractsWithComposerPackages;

    /**
     * The console command signature.
     */
    protected ?string $signature = 'install:gpio
                    {--composer=global : Absolute path to the Composer binary which should be used to install packages}
                    {--force : Overwrite any existing published files}';

    /**
     * The console command description.
     */
    protected string $description = 'Install the ScrapyardIO GPIO Framework scaffolding';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->requireComposerPackages($this->option('composer'), [
            'scrapyard-io/gpio-framework:^0.6.0',
        ])) {
            $this->components->error('Unable to install [scrapyard-io/gpio-framework].');

            return self::FAILURE;
        }

        if (! $this->publishInstalledProvider('GeneralPurposeIO\\Core\\GPIOServiceProvider')) {
            $this->components->error('Unable to publish GPIO configuration.');

            return self::FAILURE;
        }

        $this->components->info('GPIO Framework scaffolding installed successfully.');

        return self::SUCCESS;
    }
}
