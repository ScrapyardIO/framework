<?php

namespace Fabricate\Console;

use Fabricate\Console\Contracts\CommandMutex;

/**
 * Fallback mutex used when no cache-backed CommandMutex is bound.
 */
class NullCommandMutex implements CommandMutex
{
    /**
     * Attempt to obtain a command mutex for the given command.
     *
     * @param \Fabricate\Console\Command $command
     * @return bool
     */
    public function create(Command $command): bool
    {
        return true;
    }

    /**
     * Determine if a command mutex exists for the given command.
     *
     * @param \Fabricate\Console\Command $command
     * @return bool
     */
    public function exists(Command $command): bool
    {
        return false;
    }

    /**
     * Release the mutex for the given command.
     *
     * @param \Fabricate\Console\Command $command
     * @return bool
     */
    public function forget(Command $command): bool
    {
        return true;
    }
}
