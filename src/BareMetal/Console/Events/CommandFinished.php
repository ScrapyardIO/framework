<?php

namespace BareMetal\Console\Events;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandFinished
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $command,
        public InputInterface $input,
        public OutputInterface $output,
        public int $exit_code,
    ) {
    }
}
