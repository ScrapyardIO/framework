<?php

namespace Fabricate\Core\Bootstrap;

use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Core\Program;
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
     * @param Program $program
     * @return void
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function bootstrap(Program $program): void
    {
        if (! $program->bound('config_loaded_from_cache') ||
            $program->make('config_loaded_from_cache') === false) {
            $this->mergeAdditionalProviders($program);
        }

        $program->registerConfiguredProviders();
    }

    /**
     * Merge the additional configured providers into the configuration.
     *
     * @param  \Fabricate\Core\Program  $program
     * @throws BindingResolutionException|CircularDependencyException
     */
    protected function mergeAdditionalProviders(Program $program): void
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

        $program->make('config')->set(
            'machine.providers',
            array_merge(
                $program->make('config')->get('machine.providers') ?? ServiceProvider::defaultProviders()->toArray(),
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