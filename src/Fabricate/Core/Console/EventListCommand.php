<?php

namespace Fabricate\Core\Console;

use Closure;
use Fabricate\Console\Command;
use Fabricate\NutsAndBolts\Collection;
use ReflectionFunction;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'event:list')]
class EventListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected ?string $signature = 'event:list
        {--event= : Filter the events by name}
        {--json : Output the events and listeners as JSON}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = "List the application's events and listeners";

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $events = $this->getEvents()->sortKeys();

        if ($events->isEmpty()) {
            if ($this->option('json')) {
                $this->output->writeln('[]');
            } else {
                $this->components->info("Your application doesn't have any events matching the given criteria.");
            }

            return;
        }

        if ($this->option('json')) {
            $this->displayJson($events);
        } else {
            $this->displayForCli($events);
        }
    }

    /**
     * Display events and their listeners in JSON.
     */
    protected function displayJson(Collection $events): void
    {
        $data = $events->map(function ($listeners, $event) {
            return [
                'event' => strip_tags($event),
                'listeners' => (new Collection($listeners))->map(fn ($listener) => strip_tags($listener))->values()->all(),
            ];
        })->values();

        $this->output->writeln($data->toJson());
    }

    /**
     * Display the events and their listeners for the CLI.
     */
    protected function displayForCli(Collection $events): void
    {
        $this->newLine();

        $events->each(function ($listeners, $event) {
            $this->components->twoColumnDetail($event);
            $this->components->bulletList($listeners);
        });

        $this->newLine();
    }

    /**
     * Get all of the events and listeners configured for the application.
     */
    protected function getEvents(): Collection
    {
        $events = new Collection($this->getListenersOnDispatcher());

        if ($this->filteringByEvent()) {
            $events = $this->filterEvents($events);
        }

        return $events;
    }

    /**
     * Get the event / listeners from the dispatcher object.
     *
     * @return array<string, array<int, string>>
     */
    protected function getListenersOnDispatcher(): array
    {
        $events = [];

        foreach ($this->getRawListeners() as $event => $rawListeners) {
            foreach ($rawListeners as $rawListener) {
                if (is_string($rawListener)) {
                    $events[$event][] = $rawListener;
                } elseif ($rawListener instanceof Closure) {
                    $events[$event][] = $this->stringifyClosure($rawListener);
                } elseif (is_array($rawListener) && count($rawListener) === 2) {
                    if (is_object($rawListener[0])) {
                        $rawListener[0] = get_class($rawListener[0]);
                    }

                    $events[$event][] = implode('@', $rawListener);
                }
            }
        }

        return $events;
    }

    /**
     * Get a displayable string representation of a Closure listener.
     */
    protected function stringifyClosure(Closure $rawListener): string
    {
        $reflection = new ReflectionFunction($rawListener);

        $path = str_replace([base_path(), DIRECTORY_SEPARATOR], ['', '/'], $reflection->getFileName() ?: '');

        return 'Closure at: '.$path.':'.$reflection->getStartLine();
    }

    /**
     * Filter the given events using the provided event name filter.
     */
    protected function filterEvents(Collection $events): Collection
    {
        if (! $eventName = $this->option('event')) {
            return $events;
        }

        return $events->filter(
            fn ($listeners, $event) => str_contains($event, $eventName)
        );
    }

    /**
     * Determine whether the user is filtering by an event name.
     */
    protected function filteringByEvent(): bool
    {
        return ! empty($this->option('event'));
    }

    /**
     * Gets the raw version of event listeners from the event dispatcher.
     */
    protected function getRawListeners(): array
    {
        return $this->getEventsDispatcher()->getRawListeners();
    }

    /**
     * Get the event dispatcher.
     */
    public function getEventsDispatcher(): \Fabricate\Contracts\Events\Dispatcher
    {
        return $this->scrapyard_io->make('events');
    }
}
