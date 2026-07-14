<?php

namespace BareMetal\Contracts\Core;

use RuntimeException;
use BareMetal\Contracts\Chassis\Chassis;
use ScrapyardIO\NutsAndBolts\ServiceProvider;

interface Machine extends Chassis
{
    /**
     * Get the version number of the application.
     */
    public function version(): string;

    /**
     * Get the base path of the ScrapyardIO installation.
     */
    public function basePath(string $path = ''): string;

    /**
     * Get the path to the bootstrap directory.
     */
    public function bootstrapPath(string $path = ''): string;

    /**
     * Get the path to the application configuration files.
     */
    public function configPath(string $path = ''): string;

    /**
     * Get the path to the database directory.
     */
    public function databasePath(string $path = ''): string;

    /**
     * Get the path to the storage directory.
     */
    public function storagePath(string $path = ''): string;

    /**
     * Get or check the current application environment.
     */
    public function environment(string|array ...$environments): string|bool;

    /**
     * Determine if the application is running in the console.
     */
    public function runningInConsole(): bool;

    /**
     * Determine if the application is running unit tests.
     */
    public function runningUnitTests(): bool;

    /**
     * Determine if the application is running with debug mode enabled.
     */
    public function hasDebugModeEnabled(): bool;

    /**
     * Register every configured provider.
     */
    public function registerConfiguredProviders(): void;

    /**
     * Register a service provider with the application.
     */
    public function register(ServiceProvider|string $provider, bool $force = false): ServiceProvider;

    /**
     * Register a deferred provider and service.
     */
    public function registerDeferredProvider(string $provider, ?string $service = null): void;

    /**
     * Resolve a service provider instance from the class name.
     */
    public function resolveProvider(string $provider): ServiceProvider;

    /**
     * Boot the application's service providers.
     */
    public function boot(): void;

    /**
     * Register a new boot listener.
     */
    public function booting(callable $callback): void;

    /**
     * Register a new "booted" listener.
     */
    public function booted(callable $callback): void;

    /**
     * Run the given array of bootstrap classes.
     */
    public function bootstrapWith(array $bootstrappers): void;

    /**
     * Get the current application locale.
     */
    public function getLocale(): string;

    /**
     * Get the application namespace.
     * @throws RuntimeException
     */
    public function getNamespace(): string;

    /**
     * Get the registered service provider instances if any exist.
     */
    public function getProviders(ServiceProvider|string $provider): array;

    /**
     * Determine if the application has been bootstrapped before.
     */
    public function hasBeenBootstrapped(): bool;

    /**
     * Load and boot every remaining deferred provider.
     */
    public function loadDeferredProviders(): void;

    /**
     * Set the current application locale.
     */
    public function setLocale(string $locale): void;

    /**
     * Determine if middleware has been disabled for the application.
     */
    public function shouldSkipMiddleware(): bool;

    /**
     * Register a terminating callback with the application.
     */
    public function terminating(callable|string $callback): Machine;

    /**
     * Terminate the application.
     */
    public function terminate(): void;

    /**
     * Get the path to the environment file directory.
     */
    public function environmentPath(): string;

    /**
     * Set the environment file to be loaded during bootstrapping.
     */
    public function loadEnvironmentFrom(string $file): Machine;

    /**
     * Get the environment file the application is using.
     */
    public function environmentFile(): string;
}
