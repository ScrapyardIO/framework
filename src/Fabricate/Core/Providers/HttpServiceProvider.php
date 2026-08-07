<?php

namespace Fabricate\Core\Providers;

use Fabricate\Http\Client\Factory;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

/**
 * Binds the outbound HTTP client factory as `http`.
 *
 * Core owns this glue — not fabricate/http.
 */
class HttpServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->container->singleton('http', function ($app) {
            return new Factory($app->bound('events') ? $app->make('events') : null);
        });

        $this->container->singleton(Factory::class, function ($app) {
            return $app->make('http');
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['http', Factory::class];
    }
}
