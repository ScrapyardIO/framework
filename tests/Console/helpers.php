<?php

use Fabricate\Chassis\Chassis;
use Fabricate\Core\Bootstrap\BootProviders;
use Fabricate\Core\Bootstrap\LoadConfiguration;
use Fabricate\Core\Bootstrap\RegisterMagicAliases;
use Fabricate\Core\Bootstrap\RegisterProviders;
use Fabricate\Core\Machine;

if (! function_exists('createConsoleTestBasePath')) {
    function createConsoleTestBasePath(): string
    {
        $basePath = sys_get_temp_dir().'/scrapyard-io-console-'.uniqid();
        mkdir($basePath, 0777, true);
        mkdir($basePath.'/bootstrap/cache', 0777, true);
        mkdir($basePath.'/config', 0777, true);
        mkdir($basePath.'/app', 0777, true);
        mkdir($basePath.'/storage/framework/cache/data', 0777, true);

        file_put_contents($basePath.'/composer.json', json_encode([
            'name' => 'scrapyard-io/console-test',
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        file_put_contents($basePath.'/.env', "APP_KEY=\nAPP_ENV=testing\nCACHE_STORE=array\n");
        file_put_contents($basePath.'/bootstrap/app.php', <<<'PHP'
<?php

use Fabricate\Core\Machine;

return Machine::configure(basePath: dirname(__DIR__))->create();
PHP);
        file_put_contents($basePath.'/config/machine.php', <<<'PHP'
<?php

return [
    'name' => env('APP_NAME', 'ScrapyardIO'),
    'env' => env('APP_ENV', 'testing'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [],
    'providers' => null,
];
PHP);

        copy(
            dirname(__DIR__, 2).'/config/hashing.php',
            $basePath.'/config/hashing.php'
        );

        return $basePath;
    }
}

if (! function_exists('destroyConsoleTestBasePath')) {
    function destroyConsoleTestBasePath(string $basePath): void
    {
        if (! is_dir($basePath)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($basePath);
    }
}

if (! function_exists('bootstrapConsoleMachine')) {
    function bootstrapConsoleMachine(string $basePath): Machine
    {
        Chassis::setInstance(null);
        RegisterProviders::flushState();
        LoadConfiguration::alwaysUse(null);

        $_ENV['APP_ENV'] = 'testing';

        /** @var Machine $app */
        $app = Machine::configure($basePath)->create();

        $app->bootstrapWith([
            LoadConfiguration::class,
            RegisterMagicAliases::class,
            RegisterProviders::class,
            BootProviders::class,
        ]);

        return $app;
    }
}
