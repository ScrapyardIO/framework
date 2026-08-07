<?php

namespace Fabricate\Core\Providers;

use Fabricate\Chassis\Contracts\WireframeServiceContainer;
use Fabricate\Chassis\Exceptions\BindingResolutionException;
use Fabricate\Chassis\Exceptions\CircularDependencyException;
use Fabricate\Console\Events\CommandFinished;
use Fabricate\Console\Scheduling\Schedule;
use Fabricate\Contracts\Chassis\ChassisException;
use Fabricate\Contracts\Console\CLIKernel as ConsoleKernel;
use Fabricate\Contracts\Core\ExceptionRenderer;
use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\Core\Console\CliDumper;
use Fabricate\Log\Events\MessageLogged;
use Fabricate\NutsAndBolts\AggregateServiceProvider;
use Fabricate\NutsAndBolts\Defer\DeferredCallbackCollection;
use Fabricate\Testing\LoggedExceptionCollection;
use ReflectionException;
use Symfony\Component\VarDumper\Caster\StubCaster;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;

class CoreServiceProvider extends AggregateServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        if ($this->container->hasDebugModeEnabled() && ! $this->container->has(ExceptionRenderer::class)) {
            /*$this->container->make(Listener::class)->registerListeners(
                $this->container->make(Dispatcher::class)
            );*/
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     * @throws ReflectionException|ChassisException
     */
    public function register(): void
    {
        parent::register();

        $this->registerConsoleSchedule();
        $this->registerDumper();
        $this->registerDeferHandler();
        $this->registerExceptionTracking();
    }

    /**
     * Register the console schedule implementation.
     *
     * @return void
     */
    public function registerConsoleSchedule(): void
    {
        $this->container->singleton(Schedule::class, function ($container) {
            return $container->make(ConsoleKernel::class)->resolveConsoleSchedule();
        });
    }

    /**
     * Register a CLI var dumper (with source) to debug variables.
     *
     * ScrapyardIO is Workshop/CLI-first — no HTML dumper branch.
     *
     * @return void
     */
    public function registerDumper(): void
    {
        //AbstractCloner::$defaultCasters[ConnectionInterface::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[WireframeServiceContainer::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[Dispatcher::class] ??= [StubCaster::class, 'cutInternals'];
        //AbstractCloner::$defaultCasters[Grammar::class] ??= [StubCaster::class, 'cutInternals'];

        $format = $_SERVER['VAR_DUMPER_FORMAT'] ?? null;

        // Leave Symfony server/tcp dump formats alone when explicitly requested.
        if ($format === 'server' || ($format && parse_url($format, PHP_URL_SCHEME) === 'tcp')) {
            return;
        }

        if (! in_array(PHP_SAPI, ['cli', 'phpdbg'], true) && $format !== 'cli') {
            return;
        }

        $basePath = $this->container instanceof Program
            ? $this->container->basePath()
            : (string) ($this->container->bound('path.base') ? $this->container->make('path.base') : getcwd());

        $compiledViewPath = $this->container->bound('config')
            ? (string) ($this->container['config']->get('view.compiled') ?? '')
            : '';

        CliDumper::register($basePath, $compiledViewPath);
    }

    /**
     * Register the "defer" function termination handler.
     *
     * @return void
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
     */
    protected function registerDeferHandler(): void
    {
        $this->container->scoped(DeferredCallbackCollection::class);

        $this->container['events']->listen(function (CommandFinished $event) {
            app(DeferredCallbackCollection::class)->invokeWhen(fn ($callback) => app()->runningInConsole() && ($event->exitCode === 0 || $callback->always));
        });

        // JobAttempted defer listener deferred until Queue is restored.
        /*$this->container['events']->listen(function (JobAttempted $event) {
            if (in_array($event->connectionName, ['sync', 'deferred'])) {
                return;
            }

            app(DeferredCallbackCollection::class)->invokeWhen(fn ($callback) => ($event->successful() || $callback->always));
        });*/
    }

    /**
     * Register an event listener to track logged exceptions.
     *
     * @return void
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
     */
    protected function registerExceptionTracking(): void
    {
        if (! $this->container->runningUnitTests()) {
            return;
        }

        $this->container->instance(
            LoggedExceptionCollection::class,
            new LoggedExceptionCollection()
        );

        $this->container->make('events')->listen(MessageLogged::class, function ($event) {
            if (isset($event->context['exception'])) {
                $this->container->make(LoggedExceptionCollection::class)
                    ->push($event->context['exception']);
            }
        });
    }
}