<?php

require __DIR__.'/../Console/helpers.php';

use Fabricate\Core\MagicAliases\DB;
use Fabricate\Database\DatabaseManager;

test('sqlite in-memory database supports schema writes and reads', function () {
    $basePath = createConsoleTestBasePath();
    try {
        copy(dirname(__DIR__, 2).'/config/database.php', $basePath.'/config/database.php');
        $app = bootstrapConsoleMachine($basePath);
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        expect($app->make('db'))->toBeInstanceOf(DatabaseManager::class);

        DB::statement('create table scraps (id integer primary key autoincrement, name varchar not null)');
        DB::table('scraps')->insert(['name' => 'Copper']);

        expect(DB::table('scraps')->value('name'))->toBe('Copper');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
