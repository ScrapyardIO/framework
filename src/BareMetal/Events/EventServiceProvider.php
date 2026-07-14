<?php

namespace BareMetal\Events;

use ScrapyardIO\NutsAndBolts\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton('events', function ($app) {
            return new Dispatcher($app);
        });
    }
}
