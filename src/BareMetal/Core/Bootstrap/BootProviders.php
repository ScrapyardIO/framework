<?php

namespace BareMetal\Core\Bootstrap;

use BareMetal\Contracts\Core\Application as ScrapyardAppInterface;

class BootProviders
{
    /**
     * Bootstrap the given application.
     *
     */
    public function bootstrap(ScrapyardAppInterface $app): void
    {
        $app->boot();
    }
}
