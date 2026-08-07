<?php

namespace Fabricate\Broadcasting;

use Fabricate\Contracts\Broadcasting\HasBroadcastChannel;

class PrivateChannel extends Channel
{
    public function __construct(HasBroadcastChannel|string $name)
    {
        parent::__construct('private-'.($name instanceof HasBroadcastChannel ? $name->broadcastChannel() : $name));
    }
}
