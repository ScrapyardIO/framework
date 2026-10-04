<?php

namespace GeneralPurposeIO\Contracts\NutsAndBolts;

/**
 * Work that runs against a live transport wherever it lands: a pool worker, or a loop fiber on a bridge that
 * cannot leave the process. It uses the transport's blocking methods. Scalar state only: it may cross a pipe.
 */
interface BusJob
{
    public function run(GPIOTransport $bus): mixed;
}
