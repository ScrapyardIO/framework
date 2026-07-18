<?php

namespace Fabricate\Console;

/**
 * Fallback mutex used when no cache-backed CommandMutex is bound.
 */
class NullCommandMutex implements CommandMutex
{
    /**
     * Attempt to obtain a command mutex for the given command.
     *
     * @param  \Fabricate\Console\Command  $command
     * @return bool
     */
    public function create($command)
    {
        return true;
    }

    /**
     * Determine if a command mutex exists for the given command.
     *
     * @param  \Fabricate\Console\Command  $command
     * @return bool
     */
    public function exists($command)
    {
        return false;
    }

    /**
     * Release the mutex for the given command.
     *
     * @param  \Fabricate\Console\Command  $command
     * @return bool
     */
    public function forget($command)
    {
        return true;
    }
}
