<?php

namespace Fabricate\Console\Events;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandFinished
{
    /**
     * Create a new event instance.
     *
     * @param  string  $command  The command name.
     * @param InputInterface $input  The console input implementation.
     * @param OutputInterface $output  The command output implementation.
     * @param  int  $exitCode  The command exit code.
     */
    public function __construct(
        public string $command,
        public InputInterface $input,
        public OutputInterface $output,
        public int $exitCode,
    ) {
    }
}
