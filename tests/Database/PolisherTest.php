<?php

require_once __DIR__.'/../Console/helpers.php';

use Fabricate\Core\MagicAliases\DB;
use Fabricate\Database\Polisher\Model;

class DatabaseTestScrap extends Model
{
    protected $table = 'scraps';
    protected $guarded = [];
    public $timestamps = false;
}

test('polisher model performs sqlite crud', function () {
    $basePath = createConsoleTestBasePath();
    try {
        copy(dirname(__DIR__, 2).'/config/database.php', $basePath.'/config/database.php');
        $app = bootstrapConsoleMachine($basePath);
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        DB::statement('create table scraps (id integer primary key autoincrement, name varchar not null)');
        $scrap = DatabaseTestScrap::create(['name' => 'Steel']);
        $scrap->update(['name' => 'Polished Steel']);

        expect($scrap->exists)->toBeTrue()
            ->and(DatabaseTestScrap::findOrFail($scrap->getKey())->name)->toBe('Polished Steel');

        $scrap->delete();
        expect(DatabaseTestScrap::count())->toBe(0);
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
