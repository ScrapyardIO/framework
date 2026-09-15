<?php

namespace GeneralPurposeIO\Circuits;

use Voyager\Contracts\Vessel\BindingResolutionException;
use Voyager\Contracts\Vessel\Vessel;
use Voyager\System\Console\AboutCommand;
use Voyager\System\Application;
use Voyager\Contracts\NutsAndBolts\DeferrableProvider;
use Voyager\NutsAndBolts\ServiceProvider;
use GeneralPurposeIO\Circuits\Console\CircuitMakeProfileCommand;
use GeneralPurposeIO\Circuits\Enums\CircuitConsoleCommand;
use GeneralPurposeIO\Circuits\Support\CircuitCatalogAboutRows;
use GeneralPurposeIO\Contracts\Circuits\CircuitRegistry as RegistryContract;

class CircuitsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->publishConfig();

        $this->app->singleton('circuit', fn (Vessel $program) => new CircuitRegistry);
        $this->app->alias('circuit', CircuitRegistry::class);
        $this->app->alias('circuit', RegistryContract::class);

        $this->app->singleton(CircuitMakeProfileCommand::class);
        $this->commands([
            CircuitMakeProfileCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->registerAboutSection();
    }

    /**
     * Contribute catalog IC inventory to Workshop `about`.
     *
     * Rows resolve at `about` run-time (after IC packages register), not at boot.
     * Catalog only — never config/circuits.php profiles.
     */
    protected function registerAboutSection(): void
    {
        if (! class_exists(AboutCommand::class)) {
            return;
        }

        AboutCommand::add('Integrated Circuits', function (): array {
            /** @var CircuitRegistry $registry */
            $registry = $this->app->make(CircuitRegistry::class);

            return CircuitCatalogAboutRows::for($registry);
        });
    }

    /**
     * @throws BindingResolutionException
     */
    protected function publishConfig(): void
    {
        $source = dirname(__DIR__, 3).'/config/circuits.php';

        if ($this->app instanceof Application && $this->app->runningInConsole()) {
            $this->publishes(
                [$source => $this->app->configPath('circuits.php')],
                CircuitConsoleCommand::PUBLISH_CONFIG_TAG->value,
            );
        }

        $this->mergeConfigFrom($source, 'circuits');
    }

    public function provides(): array
    {
        return [
            'circuit',
            CircuitMakeProfileCommand::class,
        ];
    }
}
