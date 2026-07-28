<?php

namespace Fabricate\Queue\Connectors;

use Fabricate\Queue\DeferredQueue;

class DeferredConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @return \Fabricate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new DeferredQueue($config['after_commit'] ?? null);
    }
}
