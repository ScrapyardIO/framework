<?php

namespace Fabricate\Core\Providers;

use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Validation\Factory;

/**
 * Binds the validation factory as `validator`.
 *
 * Core owns this glue — not fabricate/validation.
 *
 * Database presence and uncompromised password verifiers are intentionally not
 * registered until fabricate/database and HTTP client bindings exist.
 */
class ValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('validator', function ($app) {
            $validator = new Factory($app->make('translator'), $app);

            if (isset($app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });

        $this->container->alias('validator', Factory::class);
    }
}
