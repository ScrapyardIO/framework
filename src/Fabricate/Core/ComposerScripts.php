<?php

namespace Fabricate\Core;

use Composer\Script\Event;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Fabricate\Contracts\Chassis\ChassisException;
use Fabricate\Core\Bootstrap\LoadConfiguration;
use Fabricate\Core\Bootstrap\LoadEnvironmentVariables;
use ReflectionException;
use Throwable;

class ComposerScripts
{
    /**
     * Handle the post-install Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postInstall(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::clearCompiled();
    }

    /**
     * Handle the post-update Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postUpdate(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::clearCompiled();
    }

    /**
     * Handle the post-autoload-dump Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postAutoloadDump(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::clearCompiled();
    }

    /**
     * Handle the pre-package-uninstall Composer event.
     *
     * @param  \Composer\Installer\PackageEvent  $event
     * @return void
     */
    public static function prePackageUninstall(PackageEvent $event)
    {
        // Package uninstall events are only applicable when uninstalling packages in dev environments...
        if (! $event->isDevMode()) {
            return;
        }

        $eventName = null;

        try {
            require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

            $machine = new Machine(getcwd());

            $machine->bootstrapWith([
                LoadEnvironmentVariables::class,
                LoadConfiguration::class,
            ]);

            /*
             * Deferred until Encryption + Concurrency ProcessDriver land:
             *
             * (new EncryptionServiceProvider($machine))->register();
             * $machine->make(ProcessDriver::class)->run(
             *     static fn () => app()['events']->dispatch($eventName)
             * );
             */

            $name = $event->getOperation()->getPackage()->getName();
            $eventName = "composer_package.{$name}:pre_uninstall";

            if ($machine->bound('events')) {
                $machine['events']->dispatch($eventName);
            }
        } catch (Throwable $e) {
            // Ignore any errors to allow the package removal to complete...
            $event->getIO()->write('There was an error dispatching or handling the ['.($eventName ?? 'unknown').'] event. Continuing with package removal...');
            $event->getIO()->writeError('Exception message: '.$e->getMessage(), verbosity: IOInterface::VERBOSE);
        }
    }

    /**
     * Clear the cached ScrapyardIO bootstrapping files.
     *
     * @return void
     * @throws ReflectionException|ChassisException
     */
    protected static function clearCompiled(): void
    {
        $machine = new Machine(getcwd());

        if (is_file($configPath = $machine->getCachedConfigPath())) {
            @unlink($configPath);
        }

        if (is_file($servicesPath = $machine->getCachedServicesPath())) {
            @unlink($servicesPath);
        }

        if (is_file($packagesPath = $machine->getCachedPackagesPath())) {
            @unlink($packagesPath);
        }
    }
}