<?php

namespace Fabricate\Contracts\Broadcasting;

interface ShouldBroadcast
{
    public function broadcastOn();
}
