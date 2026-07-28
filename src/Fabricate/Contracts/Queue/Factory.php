<?php

namespace Fabricate\Contracts\Queue;

interface Factory
{
    /**
     * Resolve a queue connection instance.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Fabricate\Contracts\Queue\Queue
     */
    public function connection($name = null);
}
