<?php

namespace Fabricate\Database\Console\Seeds;

use Fabricate\Console\Command;
use Fabricate\Console\ConfirmableTrait;
use Fabricate\Console\Prohibitable;
use Fabricate\Database\ConnectionResolverInterface as Resolver;
use Fabricate\Database\Polisher\Model;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'db:seed')]
class SeedCommand extends Command
{
    use ConfirmableTrait, Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'db:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Seed the database with records';

    /**
     * The connection resolver instance.
     *
     * @var \Fabricate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * Create a new database seed command instance.
     *
     * @param  \Fabricate\Database\ConnectionResolverInterface  $resolver
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();

        $this->resolver = $resolver;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->isProhibited() ||
            ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $this->components->info('Seeding database.');

        $previousConnection = $this->resolver->getDefaultConnection();

        $this->resolver->setDefaultConnection($this->getDatabase());

        Model::unguarded(function () {
            $this->getSeeder()->__invoke();
        });

        if ($previousConnection) {
            $this->resolver->setDefaultConnection($previousConnection);
        }

        return 0;
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Fabricate\Database\Seeder
     */
    protected function getSeeder()
    {
        $class = $this->input->getArgument('class') ?? $this->input->getOption('class');

        if (! str_contains($class, '\\')) {
            $class = 'Database\\Seeders\\'.$class;
        }

        if ($class === 'Database\\Seeders\\DatabaseSeeder' &&
            ! class_exists($class)) {
            $class = 'DatabaseSeeder';
        }

        return $this->scrapyard_io->make($class)
            ->setContainer($this->scrapyard_io)
            ->setCommand($this);
    }

    /**
     * Get the name of the database connection to use.
     *
     * @return string
     */
    protected function getDatabase()
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->scrapyard_io['config']['database.default'];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['class', InputArgument::OPTIONAL, 'The class name of the root seeder', null],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'Database\\Seeders\\DatabaseSeeder'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
