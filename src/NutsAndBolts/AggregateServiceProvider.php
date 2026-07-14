<?php

namespace ScrapyardIO\NutsAndBolts;

class AggregateServiceProvider extends ServiceProvider
{
    /**
     * The provider class names.
     */
    protected array $providers = [];

    /**
     * An array of the service provider instances.
     */
    protected array $instances = [];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->instances = [];

        foreach ($this->providers as $provider) {
            $this->instances[] = $this->app->register($provider);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        $provides = [];

        foreach ($this->providers as $provider) {
            $instance = $this->app->resolveProvider($provider);

            $provides = array_merge($provides, $instance->provides());
        }

        return $provides;
    }
}
