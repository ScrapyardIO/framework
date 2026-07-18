<?php

namespace Fabricate\Core\Bootstrap;

use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Core\Machine;
use Fabricate\NutsAndBolts\ServiceProvider;

class RegisterProviders
{
    /**
     * The service providers that should be merged before registration.
     *
     * @var array
     */
    protected static array $merge = [];

    /**
     * The path to the bootstrap provider configuration file.
     *
     * @var string|null
     */
    protected static ?string $bootstrapProviderPath;

    /**
     * Bootstrap the given application.
     *
     * @param Machine $app
     * @return void
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function bootstrap(Machine $app): void
    {
        if (! $app->bound('config_loaded_from_cache') ||
            $app->make('config_loaded_from_cache') === false) {
            $this->mergeAdditionalProviders($app);
        }

        $app->registerConfiguredProviders();
    }

    /**
     * Merge the additional configured providers into the configuration.
     *
     * @param  \Fabricate\Core\Machine  $app
     * @throws BindingResolutionException|CircularDependencyException
     */
    protected function mergeAdditionalProviders(Machine $app): void
    {
        if (static::$bootstrapProviderPath &&
            file_exists(static::$bootstrapProviderPath)) {
            $packageProviders = require static::$bootstrapProviderPath;

            foreach ($packageProviders as $index => $provider) {
                if (! class_exists($provider)) {
                    unset($packageProviders[$index]);
                }
            }
        }

        $app->make('config')->set(
            'machine.providers',
            array_merge(
                $app->make('config')->get('machine.providers') ?? ServiceProvider::defaultProviders()->toArray(),
                static::$merge,
                array_values($packageProviders ?? []),
            ),
        );
    }

    /**
     * Merge the given providers into the provider configuration before registration.
     *
     * @param  array  $providers
     * @param  string|null  $bootstrapProviderPath
     * @return void
     */
    public static function merge(array $providers, ?string $bootstrapProviderPath = null): void
    {
        static::$bootstrapProviderPath = $bootstrapProviderPath;

        static::$merge = array_values(array_filter(array_unique(
            array_merge(static::$merge, $providers)
        )));
    }

    /**
     * Flush the bootstrapper's global state.
     *
     * @return void
     */
    public static function flushState(): void
    {
        static::$bootstrapProviderPath = null;

        static::$merge = [];
    }
}
