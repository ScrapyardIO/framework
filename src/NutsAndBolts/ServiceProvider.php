<?php

namespace ScrapyardIO\NutsAndBolts;

use BareMetal\Contracts\Core\Application as ScrapyardAppInterface;

abstract class ServiceProvider
{
    /**
     * All of the registered booting callbacks.
     */
    protected array $booting_callbacks = [];

    /**
     * All of the registered booted callbacks.
     */
    protected array $booted_callbacks = [];

    public function __construct(
        protected ScrapyardAppInterface $app
    )
    {}

    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Register a booting callback to be run before the "boot" method is called.
     */
    public function booting(callable $callback): void
    {
        $this->booting_callbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     */
    public function booted(callable $callback): void
    {
        $this->booted_callbacks[] = $callback;
    }

    /**
     * Call the registered booting callbacks.
     */
    public function callBootingCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->booting_callbacks)) {
            $this->app->call($this->booting_callbacks[$index]);

            $index++;
        }
    }

    /**
     * Call the registered booted callbacks.
     */
    public function callBootedCallbacks(): void
    {
        $index = 0;

        while ($index < count($this->booted_callbacks)) {
            $this->app->call($this->booted_callbacks[$index]);

            $index++;
        }
    }
}
