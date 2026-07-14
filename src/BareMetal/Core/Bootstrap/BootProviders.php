<?php

namespace BareMetal\Core\Bootstrap;

use BareMetal\Contracts\Core\Machine;

class BootProviders
{
    /**
     * Bootstrap the given application.
     */
    public function bootstrap(Machine $app): void
    {
        $app->boot();
    }
}
