<?php

namespace Fabricate\Queue\Console;

use Fabricate\Console\Command;
use Fabricate\Contracts\Cache\Repository as Cache;
use Fabricate\NutsAndBolts\Concerns\InteractsWithTime;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:restart')]
class RestartCommand extends Command
{
    use InteractsWithTime;

    /**
     * The console command name.
     */
    protected string $name = 'queue:restart';

    /**
     * The console command description.
     */
    protected string $description = 'Restart queue worker daemons after their current job';

    /**
     * The cache store implementation.
     *
     * @var \Fabricate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new queue restart command.
     *
     * @param  \Fabricate\Contracts\Cache\Repository  $cache
     */
    public function __construct(Cache $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->cache->forever('illuminate:queue:restart', $this->currentTime());

        $this->components->info('Broadcasting queue restart signal.');
    }
}
