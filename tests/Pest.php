<?php

use Voyager\Config\Repository;
use Voyager\IOPools\IOPoolsServiceProvider;
use Voyager\System\Application;

/*
| Almost everything here is proven against plain objects and the fakes in
| tests/Support/Fakes. Protocol drivers are proven on the Pi 5 over `fnk`,
| never here.
|
| The exception is the aggregate provider, which needs register() and
| configPath(). bootedApplication() is the one real Application, and it
| lives here rather than in a test file so a filtered run still has it.
*/

/** @param array<string, mixed> $gpio */
function bootedApplication(array $gpio = []): Application
{
    $app = new Application(dirname(__DIR__));
    $app['config'] = new Repository(['gpio' => $gpio]);

    $app->register(IOPoolsServiceProvider::class);
    $app->register(GeneralPurposeIO\Core\Providers\ScrapyardIOServiceProvider::class);
    $app->boot();

    return $app;
}
