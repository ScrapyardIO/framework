<?php

namespace BareMetal\Contracts\Support;

interface DeferrableProvider
{
    /**
     * Get the services provided by the provider.
     */
    public function provides(): array;
}
