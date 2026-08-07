<?php

namespace Fabricate\Contracts\Broadcasting;

interface HasBroadcastChannel
{
    public function broadcastChannelRoute();
    public function broadcastChannel();
}
