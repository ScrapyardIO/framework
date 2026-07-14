<?php

namespace BareMetal\Core\Exceptions;

use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use WeakMap;
use BareMetal\Contracts\Chassis\Chassis;
use BareMetal\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use ScrapyardIO\NutsAndBolts\Concerns\ReflectsClosures;

class Handler implements ExceptionHandlerContract
{
    use ReflectsClosures;

    /**
     * The container implementation.
     */
    protected Chassis $container;

    /**
     * A list of the exception types that are not reported.
     */
    protected array $dont_report = [];

    /**
     * The callbacks that inspect exceptions to determine if they should be reported.

     */
    protected array $dont_report_callbacks = [];

    /**
     * The already reported exception map.
     */
    protected WeakMap $reported_exception_map;

    /**
     * Create a new exception handler instance.
     */
    public function __construct(Chassis $container)
    {
        $this->container = $container;

        $this->reported_exception_map = new WeakMap;

        $this->register();
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void {}

    public function report(Throwable $e): void
    {
        // TODO: Implement report() method.
    }

    public function shouldReport(Throwable $e): bool
    {
        // TODO: Implement shouldReport() method.
    }

    public function renderForConsole(OutputInterface $output, Throwable $e): void
    {
        // TODO: Implement renderForConsole() method.
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement @method bool isReporting(\Throwable $e)
        // TODO: Implement @method array buildContextForException()
        // TODO: Implement @method bool shouldStopRetries(\Throwable $e)
    }
}
