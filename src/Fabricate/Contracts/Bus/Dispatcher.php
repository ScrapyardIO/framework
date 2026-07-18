<?php

namespace Fabricate\Contracts\Bus;

use Fabricate\NutsAndBolts\Collection;

interface Dispatcher
{
    /**
     * Dispatch a command to its appropriate handler.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch(mixed $command): mixed;

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * Queueable jobs will be dispatched to the "sync" queue.
     *
     * @param  mixed  $command
     * @param mixed|null $handler
     * @return mixed
     */
    public function dispatchSync(mixed $command, mixed $handler = null): mixed;

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed  $command
     * @param mixed|null $handler
     * @return mixed
     */
    public function dispatchNow(mixed $command, mixed $handler = null): mixed;

    /**
     * Dispatch a command to its appropriate handler after the current process.
     *
     * @param  mixed  $command
     * @param mixed|null $handler
     * @return void
     */
    public function dispatchAfterResponse(mixed $command, mixed $handler = null): void;

    /**
     * Create a new chain of queueable jobs.
     *
     * @param array|Collection|null $jobs
     * @return mixed
     */
    public function chain(array|Collection|null $jobs = null): mixed;

    /**
     * Determine if the given command has a handler.
     *
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler(mixed $command): bool;

    /**
     * Retrieve the handler for a command.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function getCommandHandler(mixed $command): mixed;

    /**
     * Set the pipes commands should be piped through before dispatching.
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes): static;

    /**
     * Map a command to a handler.
     *
     * @param  array  $map
     * @return $this
     */
    public function map(array $map): static;
}
