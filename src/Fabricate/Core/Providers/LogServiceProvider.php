<?php

namespace Fabricate\Core\Providers;

use Fabricate\Log\LogManager;
use Fabricate\NutsAndBolts\ServiceProvider;
use Psr\Log\LoggerInterface;

/**
 * Binds the LogManager as `log`.
 *
 * Core owns this glue — not fabricate/log.
 */
class LogServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        // Machine may bind `log` early for HandleExceptions (before RegisterProviders).
        $this->container->singletonIf('log', function ($app) {
            return new LogManager($app);
        });

        $this->container->alias('log', LogManager::class);
        $this->container->alias('log', LoggerInterface::class);
    }
}
