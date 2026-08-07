<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\Core\Providers\EventServiceProvider;
use Fabricate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'event:cache')]
class EventCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'event:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = "Discover and cache the application's events and listeners";

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new event cache command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->callSilent('event:clear');

        $this->files->put(
            $this->scrapyard_io->getCachedEventsPath(),
            '<?php return '.var_export($this->getEvents(), true).';'
        );

        $this->components->info('Events cached successfully.');
    }

    /**
     * Get all of the events and listeners configured for the application.
     *
     * @return array<class-string, array>
     */
    protected function getEvents(): array
    {
        $events = [];

        foreach ($this->scrapyard_io->getProviders(EventServiceProvider::class) as $provider) {
            $providerEvents = array_merge_recursive(
                $provider->shouldDiscoverEvents() ? $provider->discoverEvents() : [],
                $provider->listens()
            );

            $events[get_class($provider)] = $providerEvents;
        }

        return $events;
    }
}
