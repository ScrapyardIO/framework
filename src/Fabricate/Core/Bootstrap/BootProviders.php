<?php

namespace Fabricate\Core\Bootstrap;

use Fabricate\Contracts\Core\Program;

class BootProviders
{
    /**
     * Bootstrap the given application.
     *
     * @param \Fabricate\Core\Bootstrap\Machine $app
     * @return void
     */
    public function bootstrap(Program $program): void
    {
        $program->boot();
    }
}
