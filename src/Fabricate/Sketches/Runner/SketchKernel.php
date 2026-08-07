<?php

namespace Fabricate\Sketches\Runner;

use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\Debug\ExceptionHandler;
use Fabricate\Contracts\Sketches\SketchKernel as SketchKernelContract;
use Fabricate\Contracts\Sketches\SketchRegistry;
use Fabricate\Core\Bootstrap\BootProviders;
use Fabricate\Core\Bootstrap\HandleExceptions;
use Fabricate\Core\Bootstrap\LoadConfiguration;
use Fabricate\Core\Bootstrap\LoadEnvironmentVariables;
use Fabricate\Core\Bootstrap\RegisterMagicAliases;
use Fabricate\Core\Bootstrap\RegisterProviders;
use Fabricate\Sketches\SketchRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SketchKernel implements SketchKernelContract
{
    protected ?RunnerInstance $runner = null;

    /**
     * @var array<int, class-string>
     */
    protected array $bootstrappers = [
        LoadEnvironmentVariables::class,
        LoadConfiguration::class,
        HandleExceptions::class,
        RegisterMagicAliases::class,
        RegisterProviders::class,
        BootProviders::class,
    ];

    /**
     * Global runner middleware stack (class-strings).
     *
     * @var array<int, class-string|callable|object>
     */
    protected array $middleware = [];

    public function __construct(
        protected Program $program,
    ) {
        if (! defined('RUNNER_BINARY')) {
            define('RUNNER_BINARY', 'runner');
        }
    }

    public function handle(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->bootstrap();

            return $this->getRunner()->run($input, $output);
        } catch (Throwable $e) {
            $this->reportException($e);
            $this->renderException($output, $e);

            return 1;
        }
    }

    public function terminate(InputInterface $input, int $status): void
    {
        //
    }

    public function bootstrap(): void
    {
        if (! $this->program->hasBeenBootstrapped()) {
            $this->program->bootstrapWith($this->bootstrappers);
        }
    }

    public function getRunner(): RunnerInstance
    {
        if (is_null($this->runner)) {
            $this->runner = new RunnerInstance(
                container: $this->program,
                registry: $this->program->make(SketchRegistry::class),
                runner: $this->program->make(SketchRunner::class),
                version: $this->program->version(),
                globalMiddleware: $this->middleware(),
            );
        }

        return $this->runner;
    }

    /**
     * @return array<int, class-string|callable|object>
     */
    protected function middleware(): array
    {
        $configured = config('sketches.middleware', []);

        return array_values(array_merge(
            $this->middleware,
            is_array($configured) ? $configured : [],
        ));
    }

    protected function reportException(Throwable $e): void
    {
        if ($this->program->bound(ExceptionHandler::class)) {
            $this->program->make(ExceptionHandler::class)->report($e);
        }
    }

    protected function renderException(OutputInterface $output, Throwable $e): void
    {
        if ($this->program->bound(ExceptionHandler::class)) {
            $this->program->make(ExceptionHandler::class)->renderForConsole($output, $e);

            return;
        }

        $output->writeln('<error>'.$e->getMessage().'</error>');
    }
}
