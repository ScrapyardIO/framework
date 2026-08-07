<?php

namespace Fabricate\Database\Console;

use Fabricate\Console\Command;
use Fabricate\Console\Prohibitable;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\Database\Connection;
use Fabricate\Database\ConnectionResolverInterface;
use Fabricate\Database\Events\MigrationsPruned;
use Fabricate\Database\Events\SchemaDumped;
use Fabricate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schema:dump')]
class DumpCommand extends Command
{
    use Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schema:dump
                {--database= : The database connection to use}
                {--path= : The path where the schema dump file should be stored}
                {--prune : Delete all existing migration files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump the given database schema';

    /**
     * Execute the console command.
     *
     * @param  \Fabricate\Database\ConnectionResolverInterface  $connections
     * @param  \Fabricate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function handle(ConnectionResolverInterface $connections, Dispatcher $dispatcher)
    {
        if ($this->isProhibited()) {
            return Command::FAILURE;
        }

        $connection = $connections->connection($database = $this->input->getOption('database'));

        $this->schemaState($connection)->dump(
            $connection, $path = $this->path($connection)
        );

        $dispatcher->dispatch(new SchemaDumped($connection, $path));

        $info = 'Database schema dumped';

        if ($this->option('prune')) {
            (new Filesystem)->deleteDirectory(
                $path = database_path('migrations'), preserve: false
            );

            $info .= ' and pruned';

            $dispatcher->dispatch(new MigrationsPruned($connection, $path));
        }

        $this->components->info($info.' successfully.');
    }

    /**
     * Create a schema state instance for the given connection.
     *
     * @param  \Fabricate\Database\Connection  $connection
     * @return mixed
     */
    protected function schemaState(Connection $connection)
    {
        $migrations = config('database.migrations', 'migrations');

        $migrationTable = is_array($migrations) ? ($migrations['table'] ?? 'migrations') : $migrations;

        return $connection->getSchemaState()
            ->withMigrationTable($migrationTable)
            ->handleOutputUsing(function ($type, $buffer) {
                $this->output->write($buffer);
            });
    }

    /**
     * Get the path that the dump should be written to.
     *
     * @param  \Fabricate\Database\Connection  $connection
     */
    protected function path(Connection $connection)
    {
        return tap($this->option('path') ?: database_path('schema/'.$connection->getName().'-schema.sql'), function ($path) {
            (new Filesystem)->ensureDirectoryExists(dirname($path));
        });
    }
}
