<?php

namespace Fabricate\Contracts\Bus;

use Fabricate\NutsAndBolts\Collection;

interface QueueingDispatcher extends Dispatcher
{
    /**
     * Attempt to find the batch with the given ID.
     *
     * @param  string  $batchId
     * @return mixed
     */
    public function findBatch(string $batchId): mixed;

    /**
     * Create a new batch of queueable jobs.
     *
     * @param array|Collection $jobs
     * @return mixed
     */
    public function batch(array|Collection $jobs): mixed;

    /**
     * Dispatch a command to its appropriate handler behind a queue.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchToQueue(mixed $command): mixed;
}
