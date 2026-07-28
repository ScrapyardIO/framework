<?php

namespace Fabricate\Queue\Connectors;

use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\Queue\FailoverQueue;
use Fabricate\Queue\QueueManager;

class FailoverConnector implements ConnectorInterface
{
    /**
     * Create a new connector instance.
     */
    public function __construct(
        protected QueueManager $manager,
        protected Dispatcher $events
    ) {
    }

    /**
     * Establish a queue connection.
     *
     * @return \Fabricate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new FailoverQueue(
            $this->manager,
            $this->events,
            $config['connections'],
        );
    }
}
