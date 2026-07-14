<?php

namespace BareMetal\Core\Support\Providers;

use ScrapyardIO\NutsAndBolts\Arr;
use ScrapyardIO\NutsAndBolts\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     */
    protected array $listen = [];

    /**
     * The subscribers to register.
     */
    protected array $subscribe = [];

    /**
     * Indicates if events should be discovered.
     */
    protected static bool $should_discover_events = true;

    /**
     * The configured event discovery paths.
     */
    protected static ?iterable $event_discovery_paths = null;

    /**
     * Register the application's event listeners.
     */
    public function register(): void
    {
        $this->booting(function () {
            $events = $this->app->make('events');

            foreach ($this->listens() as $event => $listeners) {
                foreach (array_unique(Arr::wrap($listeners), SORT_REGULAR) as $listener) {
                    $events->listen($event, $listener);
                }
            }

            foreach ($this->subscribe as $subscriber) {
                $events->subscribe($subscriber);
            }
        });
    }

    /**
     * Boot any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the events and handlers.
     */
    public function listens(): array
    {
        return $this->listen;
    }

    /**
     * Set the globally configured event discovery paths.
     */
    public static function setEventDiscoveryPaths(iterable $paths): void
    {
        static::$event_discovery_paths = $paths;
    }

    /**
     * Disable event discovery for the application.
     */
    public static function disableEventDiscovery(): void
    {
        static::$should_discover_events = false;
    }
}
