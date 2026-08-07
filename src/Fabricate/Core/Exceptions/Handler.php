<?php

namespace Fabricate\Core\Exceptions;

use Fabricate\Chassis\Contracts\WireframeServiceContainer;
use Fabricate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Fabricate\NutsAndBolts\Concerns\ReflectsClosures;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use WeakMap;

class Handler implements ExceptionHandlerContract
{
    use ReflectsClosures;

    /**
     * The container implementation.
     */
    protected WireframeServiceContainer $container;

    /**
     * A list of the exception types that are not reported.
     *
     * @var \class-string
     */
    protected array $dont_report = [];

    /**
     * The already reported exception map.
     */
    protected WeakMap $reported_exception_map;

    /**
     * Create a new exception handler instance.
     */
    public function __construct(WireframeServiceContainer $container)
    {
        $this->container = $container;

        $this->reported_exception_map = new WeakMap;

        $this->register();
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        //
    }

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        if (! $this->shouldReport($e)) {
            return;
        }

        $this->reported_exception_map[$e] = true;

        if (method_exists($e, 'report') && $this->container->call([$e, 'report']) === false) {
            return;
        }

        if (! $this->container->bound('log')) {
            return;
        }

        try {
            $this->container->make('log')->error(
                $e->getMessage(),
                ['exception' => $e]
            );
        } catch (Throwable) {
            // Logging is optional until a log manager is bound.
        }
    }

    /**
     * Determine if the exception should be reported.
     */
    public function shouldReport(Throwable $e): bool
    {
        return ! $this->isDontReport($e);
    }

    /**
     * Determine if the exception is in the "do not report" list.
     */
    protected function isDontReport(Throwable $e): bool
    {
        foreach ($this->dont_report as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render an exception to the console.
     */
    public function renderForConsole(OutputInterface $output, Throwable $e): void
    {
        (new \Symfony\Component\Console\Application)->renderThrowable($e, $output);
    }
}
