<?php

namespace Fabricate\Cache\Console;

use Fabricate\Cache\CacheManager;
use Fabricate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cache:forget')]
class ForgetCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string|null
     */
    protected ?string $signature = 'cache:forget {key : The key to remove} {store? : The store to remove the key from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Remove an item from the cache';

    /**
     * The cache manager instance.
     *
     * @var \Fabricate\Cache\CacheManager
     */
    protected CacheManager $cache;

    /**
     * Create a new cache forget command instance.
     *
     * @param  \Fabricate\Cache\CacheManager  $cache
     */
    public function __construct(CacheManager $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->cache->store($this->argument('store'))->forget(
            $this->argument('key')
        );

        $this->components->info('The ['.$this->argument('key').'] key has been removed from the cache.');
    }
}
