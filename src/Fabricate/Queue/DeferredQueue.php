<?php

namespace Fabricate\Queue;

class DeferredQueue extends SyncQueue
{
    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     *
     * @throws \Throwable
     */
    public function push($job, $data = '', $queue = null)
    {
        return \Fabricate\NutsAndBolts\defer(fn () => parent::push($job, $data, $queue));
    }
}
