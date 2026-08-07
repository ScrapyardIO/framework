<?php

namespace Fabricate\Broadcasting;

trait InteractsWithSockets
{
    public $socket;
    public function dontBroadcastToCurrentUser() { return $this; }
    public function broadcastToEveryone() { $this->socket = null; return $this; }
}
