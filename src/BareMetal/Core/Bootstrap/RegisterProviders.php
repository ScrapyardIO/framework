<?php

namespace BareMetal\Core\Bootstrap;

use BareMetal\Core\Machine;
use ScrapyardIO\NutsAndBolts\ServiceProvider;
use BareMetal\Contracts\Chassis\BindingResolutionException;
use BareMetal\Contracts\Chassis\CircularDependencyException;

class RegisterProviders
{
    /**
     * The service providers that should be merged before registration.
     */
    protected static array $merge = [];

    /**
     * The path to the bootstrap provider configuration file.

     */
    protected static ?string $bootstrap_provider_path = null;


    /**
     * Bootstrap the given application.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws \ReflectionException
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
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws \ReflectionException
     */
    protected function mergeAdditionalProviders(Machine $app): void
    {
        if (static::$bootstrap_provider_path &&
            file_exists(static::$bootstrap_provider_path)) {
            $packageProviders = require static::$bootstrap_provider_path;

            foreach ($packageProviders as $index => $provider) {
                if (! class_exists($provider)) {
                    unset($packageProviders[$index]);
                }
            }
        }

        $app->make('config')->set(
            'scrapyard-io.providers',
            array_merge(
                $app->make('config')->get('scrapyard-io.providers') ?? ServiceProvider::defaultProviders()->toArray(),
                static::$merge,
                array_values($packageProviders ?? []),
            ),
        );
    }

    /**
     * Merge the given providers into the provider configuration before registration.
     */
    public static function merge(array $providers, ?string $bootstrap_provider_path = null): void
    {
        static::$bootstrap_provider_path = $bootstrap_provider_path;

        static::$merge = array_values(array_filter(array_unique(
            array_merge(static::$merge, $providers)
        )));
    }

    /**
     * Flush the bootstrapper's global state.
     */
    public static function flushState(): void
    {
        static::$bootstrap_provider_path = null;

        static::$merge = [];
    }
}
