<?php

require __DIR__.'/../Console/helpers.php';

use Fabricate\Contracts\Console\CLIKernel;
use Fabricate\Core\MagicAliases\DB;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function () {
    $this->basePath = createConsoleTestBasePath();
    mkdir($this->basePath.'/database/migrations', 0777, true);
    mkdir($this->basePath.'/database/seeders', 0777, true);
    mkdir($this->basePath.'/app/Models', 0777, true);

    copy(dirname(__DIR__, 2).'/config/database.php', $this->basePath.'/config/database.php');

    file_put_contents($this->basePath.'/database/migrations/2026_01_01_000000_create_scraps_table.php', <<<'PHP'
<?php

use Fabricate\Core\MagicAliases\Schema;
use Fabricate\Database\Migrations\Migration;
use Fabricate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraps');
    }
};
PHP);

    file_put_contents($this->basePath.'/database/seeders/DatabaseSeeder.php', <<<'PHP'
<?php

namespace Database\Seeders;

use Fabricate\Database\Seeder;
use Fabricate\Core\MagicAliases\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('scraps')->insert(['name' => 'Copper']);
    }
};
PHP);

    // Ensure composer autoload can find Database\Seeders in the temp app.
    $composer = json_decode(file_get_contents($this->basePath.'/composer.json'), true);
    $composer['autoload']['psr-4']['Database\\Seeders\\'] = 'database/seeders/';
    file_put_contents($this->basePath.'/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
});

afterEach(function () {
    destroyConsoleTestBasePath($this->basePath);
});

test('workshop migrate and migrate:rollback work on sqlite', function () {
    $app = bootstrapConsoleMachine($this->basePath);
    $app['config']->set('database.connections.sqlite.database', ':memory:');
    $app['config']->set('database.default', 'sqlite');

    $kernel = $app->make(CLIKernel::class);
    $output = new BufferedOutput;

    expect($kernel->call('migrate', ['--force' => true], $output))->toBe(0);
    expect(DB::getSchemaBuilder()->hasTable('scraps'))->toBeTrue();

    $rollback = new BufferedOutput;
    expect($kernel->call('migrate:rollback', ['--force' => true], $rollback))->toBe(0);
    expect(DB::getSchemaBuilder()->hasTable('scraps'))->toBeFalse();
});

test('workshop db:seed inserts records', function () {
    $app = bootstrapConsoleMachine($this->basePath);
    $app['config']->set('database.connections.sqlite.database', ':memory:');
    $app['config']->set('database.default', 'sqlite');

    // Load seeder class from temp path without composer dump.
    require_once $this->basePath.'/database/seeders/DatabaseSeeder.php';

    $kernel = $app->make(CLIKernel::class);

    expect($kernel->call('migrate', ['--force' => true], new BufferedOutput))->toBe(0);
    expect($kernel->call('db:seed', ['--force' => true], new BufferedOutput))->toBe(0);
    expect(DB::table('scraps')->value('name'))->toBe('Copper');
});

test('make:model creates a polisher model', function () {
    $app = bootstrapConsoleMachine($this->basePath);
    $kernel = $app->make(CLIKernel::class);

    expect($kernel->call('make:model', [
        'name' => 'Scrap',
        '--no-interaction' => true,
    ], new BufferedOutput))->toBe(0);

    $path = $this->basePath.'/app/Models/Scrap.php';
    expect(file_exists($path))->toBeTrue()
        ->and(file_get_contents($path))->toContain('Fabricate\\Database\\Polisher\\Model')
        ->and(file_get_contents($path))->toContain('class Scrap extends Model');
});

test('make:graph-model creates a graph polisher model', function () {
    $app = bootstrapConsoleMachine($this->basePath);
    $kernel = $app->make(CLIKernel::class);

    expect($kernel->call('make:graph-model', [
        'name' => 'ScrapNode',
        '--no-interaction' => true,
    ], new BufferedOutput))->toBe(0);

    $path = $this->basePath.'/app/Models/ScrapNode.php';
    expect(file_exists($path))->toBeTrue()
        ->and(file_get_contents($path))->toContain('Fabricate\\Graph\\Polisher\\Model')
        ->and(file_get_contents($path))->toContain("protected \$table = 'ScrapNode'");
});
