<?php

namespace Fabricate\Core\Bootstrap;

use Fabricate\Contracts\Core\Machine;

class BootProviders
{
    /**
     * Bootstrap the given application.
     *
     * @param Machine $app
     * @return void
     */
    public function bootstrap(Machine $app): void
    {
        $app->boot();
    }
}
