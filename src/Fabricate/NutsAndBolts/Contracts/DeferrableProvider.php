<?php

namespace Fabricate\NutsAndBolts\Contracts;

interface DeferrableProvider
{
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array;
}