<?php

namespace Fabricate\Core\Providers;

use Fabricate\Contracts\Core\Program;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Translation\FileLoader;
use Fabricate\Translation\Translator;

/**
 * Binds the translator and translation loader.
 *
 * Core owns this glue — not fabricate/translation.
 */
class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerLoader();

        $this->container->singleton('translator', function ($app) {
            $loader = $app->make('translation.loader');

            $locale = $app instanceof Program
                ? $app->getLocale()
                : (string) $app->make('config')->get('machine.locale', 'en');

            $trans = new Translator($loader, $locale);

            $trans->setFallback(
                $app instanceof Program && method_exists($app, 'getFallbackLocale')
                    ? $app->getFallbackLocale()
                    : (string) $app->make('config')->get('machine.fallback_locale', 'en')
            );

            return $trans;
        });

        $this->container->alias('translator', Translator::class);
    }

    protected function registerLoader(): void
    {
        $this->container->singleton('translation.loader', function ($app) {
            $paths = [
                dirname(__DIR__, 2).'/Translation/lang',
            ];

            if ($app->bound('path.lang')) {
                $paths[] = $app->make('path.lang');
            }

            return new FileLoader($app->make('files'), $paths);
        });
    }
}
