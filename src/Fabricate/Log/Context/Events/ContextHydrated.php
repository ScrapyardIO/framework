<?php

namespace Fabricate\Log\Context\Events;

use Fabricate\Log\Context\Repository;

class ContextHydrated
{
    /**
     * The context instance.
     *
     * @var Repository
     */
    public Repository $context;

    /**
     * Create a new event instance.
     *
     * @param Repository $context
     */
    public function __construct(Repository $context)
    {
        $this->context = $context;
    }
}
