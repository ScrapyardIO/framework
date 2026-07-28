<?php

namespace Fabricate\Contracts\Core;

use Closure;
use Fabricate\Contracts\Chassis\WireframeServiceContainer;
use Fabricate\NutsAndBolts\ServiceProvider;

interface Program extends WireframeServiceContainer
{
    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version(): string;

    /**
     * Get the base path of the ScrapyardIO installation.
     *
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ''): string;

    /**
     * Get the path to the bootstrap directory.
     *
     * @param string $path
     * @return string
     */
    public function bootstrapPath(string $path = ''): string;

    /**
     * Get the path to the application configuration files.
     *
     * @param string $path
     * @return string
     */
    public function configPath(string $path = ''): string;

    /**
     * Determine if the application is running with debug mode enabled.
     *
     * @return bool
     */
    public function hasDebugModeEnabled(): bool;

    /**
     * Get the path to the database directory.
     *
     * @param string $path
     * @return string
     */
    public function databasePath(string $path = ''): string;

    /**
     * Get the path to the storage directory.
     *
     * @param string $path
     * @return string
     */
    public function storagePath(string $path = ''): string;

    /**
     * Get or check the current application environment.
     *
     * @param  string|array  ...$environments
     * @return string|bool
     */
    public function environment(...$environments): bool|string;

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole(): bool;

    /**
     * Determine if the application is running unit tests.
     *
     * @return bool
     */
    public function runningUnitTests(): bool;

    /**
     * Register every configured provider.
     *
     * @return void
     */
    public function registerConfiguredProviders(): void;

    /**
     * Register a service provider with the application.
     *
     * @param string|ServiceProvider $provider
     * @param bool $force
     * @return ServiceProvider
     */
    public function register(string|ServiceProvider $provider, bool $force = false): ServiceProvider;

    /**
     * Register a deferred provider and service.
     *
     * @param string $provider
     * @param string|null $service
     * @return void
     */
    public function registerDeferredProvider(string $provider, ?string $service = null): void;

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param string $provider
     * @return ServiceProvider
     */
    public function resolveProvider(string $provider): ServiceProvider;

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot(): void;

    /**
     * Register a new boot listener.
     *
     * @param callable $callback
     * @return void
     */
    public function booting(callable $callback): void;

    /**
     * Register a new "booted" listener.
     *
     * @param callable $callback
     * @return void
     */
    public function booted(callable $callback): void;

    /**
     * Run the given array of bootstrap classes.
     *
     * @param  array  $bootstrappers
     * @return void
     */
    public function bootstrapWith(array $bootstrappers): void;

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale(): string;

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public function getNamespace(): string;

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param string|ServiceProvider $provider
     * @return array
     */
    public function getProviders(string|ServiceProvider $provider): array;

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped(): bool;

    /**
     * Load and boot every remaining deferred provider.
     *
     * @return void
     */
    public function loadDeferredProviders(): void;

    /**
     * Set the current application locale.
     *
     * @param string $locale
     * @return void
     */
    public function setLocale(string $locale): void;

    /**
     * Determine if middleware has been disabled for the application.
     *
     * @return bool
     */
    public function shouldSkipMiddleware(): bool;

    /**
     * Register a terminating callback with the application.
     *
     * @param callable|string $callback
     * @return WireframeServiceContainer
     */
    public function terminating(callable|string $callback): WireframeServiceContainer;

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate(): void;

    /**
     * Get the environment file the application is using.
     *
     * @return string
     */
    public function environmentFile(): string;

    /**
     * Set the environment file to be loaded during bootstrapping.
     *
     * @param string $file
     * @return $this
     */
    public function loadEnvironmentFrom(string $file): static;

    /**
     * Get the path to the environment file directory.
     *
     * @return string
     */
    public function environmentPath(): string;

    /**
     * Get the fully qualified path to the environment file.
     *
     * @return string
     */
    public function environmentFilePath(): string;

    /**
     * Detect the application's current environment.
     *
     * @param  Closure  $callback
     * @return string
     */
    public function detectEnvironment(Closure $callback): string;
}