<?php

namespace Fabricate\NutsAndBolts\Contracts;

interface MessageProvider
{
    /**
     * Get the messages for the instance.
     *
     * @return \Fabricate\NutsAndBolts\Contracts\MessageBag
     */
    public function getMessageBag();
}
