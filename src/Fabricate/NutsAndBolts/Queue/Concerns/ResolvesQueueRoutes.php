<?php

namespace Fabricate\NutsAndBolts\Queue\Concerns;

use Fabricate\Chassis\Chassis;
use Fabricate\Queue\QueueRoutes;

trait ResolvesQueueRoutes
{
    /**
     * Resolve the default connection name for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function resolveConnectionFromQueueRoute($queueable)
    {
        return $this->queueRoutes()->getConnection($queueable);
    }

    /**
     * Resolve the default queue name for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function resolveQueueFromQueueRoute($queueable)
    {
        return $this->queueRoutes()->getQueue($queueable);
    }

    /**
     * Get the queue routes manager instance.
     *
     * @return \Fabricate\Queue\QueueRoutes
     */
    protected function queueRoutes()
    {
        $container = Chassis::getInstance();

        return $container->bound('queue.routes')
            ? $container->make('queue.routes')
            : new QueueRoutes;
    }
}
