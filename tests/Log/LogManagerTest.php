<?php

use Fabricate\Core\Machine;
use Fabricate\Log\LogManager;
use Monolog\Handler\NullHandler;
use Psr\Log\LoggerInterface;

test('log manager build creates ondemand null channel', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-log-'.uniqid();
    mkdir($basePath, 0777, true);

    try {
        $app = new Machine($basePath);
        $manager = new LogManager($app);

        $logger = $manager->build([
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ]);

        expect($logger)->toBeInstanceOf(LoggerInterface::class);

        $logger->info('ignored by null handler');
    } finally {
        if (is_dir($basePath)) {
            rmdir($basePath);
        }
    }
});

test('log manager resolves built in null channel', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-log-'.uniqid();
    mkdir($basePath, 0777, true);

    try {
        $app = new Machine($basePath);
        $manager = new LogManager($app);

        $logger = $manager->channel('null');

        expect($logger)->toBeInstanceOf(LoggerInterface::class);
    } finally {
        if (is_dir($basePath)) {
            rmdir($basePath);
        }
    }
});
