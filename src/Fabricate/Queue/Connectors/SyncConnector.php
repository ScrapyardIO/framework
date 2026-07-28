<?php

namespace Fabricate\Queue\Connectors;

use Fabricate\Queue\SyncQueue;

class SyncConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Fabricate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new SyncQueue($config['after_commit'] ?? null);
    }
}
