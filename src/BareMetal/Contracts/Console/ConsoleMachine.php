<?php

namespace BareMetal\Contracts\Console;

use Symfony\Component\Console\Output\OutputInterface;

interface ConsoleMachine
{
    /**
     * Run a Workshop console command by name.
     */
    public function call(string $command, array $parameters = [], OutputInterface|null $output_buffer = null): int;

    /**
     * Get the output from the last command.
     */
    public function output(): string;
}
