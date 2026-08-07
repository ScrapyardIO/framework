<?php

namespace Fabricate\Database\Console\Migrations;

use Fabricate\Console\Command;
use Fabricate\Database\Migrations\MigrationRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate:install')]
class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'migrate:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Create the migration repository';

    /**
     * The repository instance.
     *
     * @var \Fabricate\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Fabricate\Database\Migrations\MigrationRepositoryInterface  $repository
     */
    public function __construct(MigrationRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->repository->setSource($this->input->getOption('database'));

        if (! $this->repository->repositoryExists()) {
            $this->repository->createRepository();
        }

        $this->components->info('Migration table created successfully.');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
        ];
    }
}
