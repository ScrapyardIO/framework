<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\Core\Console\Concerns\InteractsWithComposerPackages;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'install:fonts')]
class InstallFontsCommand extends Command
{
    use InteractsWithComposerPackages;

    /**
     * The console command signature.
     */
    protected ?string $signature = 'install:fonts
                    {--composer=global : Absolute path to the Composer binary which should be used to install packages}
                    {--force : Overwrite any existing published files}';

    /**
     * The console command description.
     */
    protected string $description = 'Install ScrapyardIO Autopen font scaffolding';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->requireComposerPackages($this->option('composer'), [
            'scrapyard-io/autopen:^0.6.0',
        ])) {
            $this->components->error('Unable to install [scrapyard-io/autopen].');

            return self::FAILURE;
        }

        if (! $this->publishInstalledProvider('ScrapyardIO\\Fonts\\AutopenServiceProvider')) {
            $this->components->error('Unable to publish Autopen configuration.');

            return self::FAILURE;
        }

        $this->components->info('Autopen font scaffolding installed successfully.');

        return self::SUCCESS;
    }
}
