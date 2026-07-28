<?php

namespace Fabricate\Queue\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Fabricate\Contracts\Queue\Queue
     */
    public function connect(array $config);
}
