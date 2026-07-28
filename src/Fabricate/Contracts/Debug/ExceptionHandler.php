<?php

namespace Fabricate\Contracts\Debug;

use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @method bool isReporting(Throwable $e)
 * @method array buildContextForException()
 * @method bool shouldStopRetries(Throwable $e)
 */

interface ExceptionHandler
{
    /**
     * Report or log an exception.
     *
     * @param  Throwable  $e
     * @return void
     *
     * @throws Throwable
     */
    public function report(Throwable $e);

    /**
     * Determine if the exception should be reported.
     *
     * @param  Throwable  $e
     * @return bool
     */
    public function shouldReport(Throwable $e): bool;

    /**
     * Render an exception to the console.
     *
     * @param OutputInterface $output
     * @param  Throwable  $e
     * @return void
     *
     * @internal This method is not meant to be used or overwritten outside the framework.
     */
    public function renderForConsole(OutputInterface $output, Throwable $e): void;
}