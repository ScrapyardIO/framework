<?php

namespace Fabricate\Core\Providers;

use Fabricate\Console\Events\CommandFinished;
use Fabricate\Console\Scheduling\Schedule;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Chassis\WireframeServiceContainer;
use Fabricate\Contracts\Console\ConsoleKernel;
use Fabricate\Contracts\Core\ExceptionRenderer;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\Core\Console\CliDumper;
use Fabricate\Core\Exceptions\Renderer\Listener;
use Fabricate\Log\Events\MessageLogged;
use Fabricate\NutsAndBolts\AggregateServiceProvider;
use Fabricate\NutsAndBolts\Defer\DeferredCallbackCollection;
use Fabricate\Queue\Events\JobAttempted;
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
        if ($this->program->hasDebugModeEnabled() && ! $this->program->has(ExceptionRenderer::class)) {
            $this->program->make(Listener::class)->registerListeners(
                $this->program->make(Dispatcher::class)
            );
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
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
        $this->program->singleton(Schedule::class, function ($program) {
            return $program->make(ConsoleKernel::class)->resolveConsoleSchedule();
        });
    }

    /**
     * Register a var dumper (with source) to debug variables.
     *
     * @return void
     */
    public function registerDumper(): void
    {
        //AbstractCloner::$defaultCasters[ConnectionInterface::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[WireframeServiceContainer::class] ??= [StubCaster::class, 'cutInternals'];
        AbstractCloner::$defaultCasters[Dispatcher::class] ??= [StubCaster::class, 'cutInternals'];
        //AbstractCloner::$defaultCasters[Grammar::class] ??= [StubCaster::class, 'cutInternals'];

        $basePath = $this->program->basePath();

        $compiledViewPath = $this->program['config']->get('view.compiled');

        $format = $_SERVER['VAR_DUMPER_FORMAT'] ?? null;

        match (true) {
            'cli' == $format => CliDumper::register($basePath, $compiledViewPath),
            'server' == $format => null,
            $format && 'tcp' == parse_url($format, PHP_URL_SCHEME) => null,
            default => in_array(PHP_SAPI, ['cli', 'phpdbg']) ? CliDumper::register($basePath, $compiledViewPath) : HtmlDumper::register($basePath, $compiledViewPath),
        };
    }

    /**
     * Register the "defer" function termination handler.
     *
     * @return void
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
     */
    protected function registerDeferHandler(): void
    {
        $this->program->scoped(DeferredCallbackCollection::class);

        $this->program['events']->listen(function (CommandFinished $event) {
            app(DeferredCallbackCollection::class)->invokeWhen(fn ($callback) => app()->runningInConsole() && ($event->exitCode === 0 || $callback->always));
        });

        $this->program['events']->listen(function (JobAttempted $event) {
            if (in_array($event->connectionName, ['sync', 'deferred'])) {
                return;
            }

            app(DeferredCallbackCollection::class)->invokeWhen(fn ($callback) => ($event->successful() || $callback->always));
        });
    }

    /**
     * Register an event listener to track logged exceptions.
     *
     * @return void
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
     */
    protected function registerExceptionTracking(): void
    {
        if (! $this->program->runningUnitTests()) {
            return;
        }

        $this->program->instance(
            LoggedExceptionCollection::class,
            new LoggedExceptionCollection()
        );

        $this->program->make('events')->listen(MessageLogged::class, function ($event) {
            if (isset($event->context['exception'])) {
                $this->program->make(LoggedExceptionCollection::class)
                    ->push($event->context['exception']);
            }
        });
    }
}