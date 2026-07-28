<?php

namespace Fabricate\Console\Contracts;

use Fabricate\Console\Command;

interface CommandMutex
{
    /**
     * Attempt to obtain a command mutex for the given command.
     *
     * @param  Command  $command
     * @return bool
     */
    public function create(Command $command): bool;

    /**
     * Determine if a command mutex exists for the given command.
     *
     * @param Command $command
     * @return bool
     */
    public function exists(Command $command): bool;

    /**
     * Release the mutex for the given command.
     *
     * @param Command $command
     * @return bool
     */
    public function forget(Command $command): bool;
}