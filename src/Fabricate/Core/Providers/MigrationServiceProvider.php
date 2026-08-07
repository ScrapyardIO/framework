<?php

namespace Fabricate\Core\Providers;

use Fabricate\Database\Migrations\DatabaseMigrationRepository;
use Fabricate\Database\Migrations\MigrationCreator;
use Fabricate\Database\Migrations\MigrationRepositoryInterface;
use Fabricate\Database\Migrations\Migrator;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

/**
 * Binds the migrator, migration repository, and creator.
 *
 * Core owns this glue — Workshop registers the console commands.
 */
class MigrationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->registerRepository();
        $this->registerMigrator();
        $this->registerCreator();
    }

    protected function registerRepository(): void
    {
        $this->container->singleton('migration.repository', function ($app) {
            $migrations = $app['config']['database.migrations'];

            $table = is_array($migrations) ? ($migrations['table'] ?? 'migrations') : $migrations;

            return new DatabaseMigrationRepository($app['db'], $table);
        });

        $this->container->bind(
            MigrationRepositoryInterface::class,
            fn ($app) => $app['migration.repository']
        );
    }

    protected function registerMigrator(): void
    {
        $this->container->singleton('migrator', function ($app) {
            return new Migrator(
                $app['migration.repository'],
                $app['db'],
                $app['files'],
                $app->bound('events') ? $app['events'] : null
            );
        });

        $this->container->bind(Migrator::class, fn ($app) => $app['migrator']);
    }

    protected function registerCreator(): void
    {
        $this->container->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files'], $app->basePath('stubs'));
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'migrator',
            'migration.repository',
            'migration.creator',
            Migrator::class,
            MigrationRepositoryInterface::class,
        ];
    }
}
