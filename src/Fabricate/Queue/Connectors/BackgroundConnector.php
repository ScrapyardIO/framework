<?php

namespace Fabricate\Queue\Connectors;

use Fabricate\Queue\BackgroundQueue;

class BackgroundConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @return \Fabricate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new BackgroundQueue($config['after_commit'] ?? null);
    }
}
