<?php

namespace Fabricate\Contracts\Sketches;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface SketchKernel
{
    /**
     * Handle an incoming runner console input.
     */
    public function handle(InputInterface $input, OutputInterface $output): int;

    /**
     * Terminate the sketch kernel.
     */
    public function terminate(InputInterface $input, int $status): void;
}
