<?php

namespace Fabricate\Core\Bootstrap;

use Fabricate\Contracts\Core\Program;

class BootProviders
{
    /**
     * Bootstrap the given application.
     *
     * @param Machine $app
     * @return void
     */
    public function bootstrap(Program $program): void
    {
        $program->boot();
    }
}
