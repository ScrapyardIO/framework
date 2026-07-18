<?php

namespace Fabricate\Core\Console;

use Exception;
use Fabricate\Console\Command;
use Fabricate\Core\PackageManifest;
use Fabricate\NutsAndBolts\Collection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'package:discover')]
class PackageDiscoverCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string|null
     */
    protected ?string $signature = 'package:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Rebuild the cached package manifest';

    /**
     * Execute the console command.
     *
     * @param PackageManifest $manifest
     * @return void
     * @throws Exception
     */
    public function handle(PackageManifest $manifest): void
    {
        $this->components->info('Discovering packages');

        $manifest->build();

        new Collection($manifest->manifest)
            ->keys()
            ->each(fn ($description) => $this->components->task($description))
            ->whenNotEmpty(function () {
                $this->newLine();
            });
    }
}
