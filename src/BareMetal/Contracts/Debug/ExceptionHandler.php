<?php

namespace BareMetal\Contracts\Debug;

use Throwable;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method bool isReporting(\Throwable $e)
 * @method array buildContextForException()
 * @method bool shouldStopRetries(\Throwable $e)
 */
interface ExceptionHandler
{
    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void;

    /**
     * Determine if the exception should be reported.
     */
    public function shouldReport(Throwable $e): bool;

    /**
     * Render an exception to the console.
     *
     * @internal This method is not meant to be used or overwritten outside the framework.
     */
    public function renderForConsole(OutputInterface $output, Throwable $e): void;
}
