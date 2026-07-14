<?php

namespace Illuminate\Contracts\Broadcasting;

use BareMetal\Contracts\Broadcasting\BroadcastException;

interface Broadcaster
{
    /**
     * Broadcast the given event.
     *
     * @throws BroadcastException
     */
    public function broadcast(array $channels, string $event, array $payload = []): void;
}
