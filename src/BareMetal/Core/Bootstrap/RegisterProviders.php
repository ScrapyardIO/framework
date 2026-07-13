<?php

namespace BareMetal\Core\Bootstrap;

use BareMetal\Contracts\Core\Application as ScrapyardAppInterface;
use Illuminate\Contracts\Container\BindingResolutionException;

class RegisterProviders
{
    /**
     * The service providers that should be merged before registration.
     */
    protected static array $merge = [];

    /**
     * The path to the bootstrap provider configuration file.
     */
    protected static ?string $bootstrap_provider_path;

    /**
     * Bootstrap the given application.
     * @throws BindingResolutionException
     */
    public function bootstrap(ScrapyardAppInterface $app): void
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
     */
    protected function mergeAdditionalProviders(ScrapyardAppInterface $app): void
    {
        if (static::$bootstrap_provider_path &&
            file_exists(static::$bootstrap_provider_path)) {
            $package_providers = require static::$bootstrap_provider_path;

            foreach ($package_providers as $index => $provider) {
                if (! class_exists($provider)) {
                    unset($package_providers[$index]);
                }
            }
        }

        $app->make('config')->set(
            'scrapyard.providers',
            array_merge(
                $app->make('config')->get('scrapyard.providers') ?? [],
                static::$merge,
                array_values($package_providers ?? []),
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
}
