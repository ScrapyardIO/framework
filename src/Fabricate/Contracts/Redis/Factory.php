<?php

namespace Fabricate\Contracts\Redis;

interface Factory
{
    /**
     * Get a Redis connection by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Fabricate\Redis\Connections\Connection
     */
    public function connection($name = null);
}
