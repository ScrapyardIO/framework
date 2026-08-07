<?php

namespace Fabricate\Core\Console;

use Closure;
use Fabricate\Console\Command;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Composer;
use Fabricate\NutsAndBolts\Stringable;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Display Machine environment / driver summary.
 *
 * Built-in sections cover only restored 0.7.x surfaces. Companion packages
 * (GPIO, displays, ICs, …) register their own sections from a service provider
 * `boot()` via {@see AboutCommand::add()} — Core never owns those domains.
 */
#[AsCommand(name: 'about')]
class AboutCommand extends Command
{
    /**
     * The console command signature.
     */
    protected ?string $signature = 'about {--only= : The section to display}
                {--json : Output the information as JSON}';

    /**
     * The console command description.
     */
    protected string $description = 'Display basic information about your Machine';

    /**
     * The Composer instance.
     */
    protected Composer $composer;

    /**
     * The data to display.
     *
     * @var array<string, list<mixed>>
     */
    protected static array $data = [];

    /**
     * The registered callables that add custom data to the command output.
     *
     * @var list<callable>
     */
    protected static array $customDataResolvers = [];

    /**
     * Create a new command instance.
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->gatherApplicationInformation();

        new Collection(static::$data)
            ->map(fn ($items) => new Collection($items)
                ->map(function ($value) {
                    if (is_array($value)) {
                        return [$value];
                    }

                    if (is_string($value)) {
                        $value = $this->scrapyard_io->make($value);
                    }

                    return new Collection($this->scrapyard_io->call($value))
                        ->map(fn ($value, $key) => [$key, $value])
                        ->values()
                        ->all();
                })->flatten(1)
            )
            // Package AboutCommand::add() rows with the same label override earlier ones.
            ->map(fn ($rows) => new Collection($rows)
                ->reduce(function (Collection $carry, array $detail) {
                    [$label] = $detail;

                    return $carry->put($label, $detail);
                }, new Collection)
                ->values()
            )
            ->sortBy(function ($data, $key) {
                $index = array_search($key, ['Environment', 'Cache', 'Drivers'], true);

                return $index === false ? 99 : $index;
            })
            ->filter(function ($data, $key) {
                return $this->option('only')
                    ? in_array($this->toSearchKeyword($key), $this->sections(), true)
                    : true;
            })
            ->pipe(fn ($data) => $this->display($data));

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Gather information about the Machine.
     */
    protected function gatherApplicationInformation(): void
    {
        self::$data = [];

        $formatEnabledStatus = fn ($value) => $value ? '<fg=yellow;options=bold>ENABLED</>' : 'OFF';
        $formatCachedStatus = fn ($value) => $value ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>';
        $formatInstalledStatus = fn ($value) => $value
            ? '<fg=green;options=bold>YES</>'
            : '<fg=yellow;options=bold>NO</>';

        static::addToSection('Environment', fn () => [
            'Application Name' => config('machine.name'),
            'ScrapyardIO Version' => $this->scrapyard_io->version(),
            'PHP Version' => phpversion(),
            'Composer Version' => $this->composer->getVersion() ?? '<fg=yellow;options=bold>-</>',
            'Environment' => $this->scrapyard_io->environment(),
            'Debug Mode' => static::format((bool) config('machine.debug'), console: $formatEnabledStatus),
            'Timezone' => config('machine.timezone', 'UTC'),
            'Locale' => config('machine.locale', 'en'),
            // Companion packages (e.g. scrapyard-io/wrench) override this via AboutCommand::add().
            'Wrench Installed' => static::format(false, console: $formatInstalledStatus),
        ]);

        static::addToSection('Cache', fn () => [
            'Config' => static::format($this->scrapyard_io->configurationIsCached(), console: $formatCachedStatus),
            'Events' => static::format($this->scrapyard_io->eventsAreCached(), console: $formatCachedStatus),
        ]);

        static::addToSection('Drivers', fn () => array_filter([
            'Cache' => function ($json) {
                $cacheStore = config('cache.default');

                if (config('cache.stores.'.$cacheStore.'.driver') === 'failover') {
                    $secondary = new Collection(config('cache.stores.'.$cacheStore.'.stores', []));

                    return value(static::format(
                        value: $cacheStore,
                        console: fn ($value) => '<fg=yellow;options=bold>'.$value.'</> <fg=gray;options=bold>/</> '.$secondary->implode(', '),
                        json: fn () => $secondary->all(),
                    ), $json);
                }

                return $cacheStore;
            },
            'Logs' => function ($json) {
                $logChannel = config('logging.default');

                if (config('logging.channels.'.$logChannel.'.driver') === 'stack') {
                    $secondary = new Collection(config('logging.channels.'.$logChannel.'.channels', []));

                    return value(static::format(
                        value: $logChannel,
                        console: fn ($value) => '<fg=yellow;options=bold>'.$value.'</> <fg=gray;options=bold>/</> '.$secondary->implode(', '),
                        json: fn () => $secondary->all(),
                    ), $json);
                }

                return $logChannel;
            },
            'Queue' => function ($json) {
                $queueConnection = config('queue.default');

                if (is_null($queueConnection)) {
                    return null;
                }

                if (config('queue.connections.'.$queueConnection.'.driver') === 'failover') {
                    $secondary = new Collection(config('queue.connections.'.$queueConnection.'.connections', []));

                    return value(static::format(
                        value: $queueConnection,
                        console: fn ($value) => '<fg=yellow;options=bold>'.$value.'</> <fg=gray;options=bold>/</> '.$secondary->implode(', '),
                        json: fn () => $secondary->all(),
                    ), $json);
                }

                return $queueConnection;
            },
            'Filesystem' => config('filesystems.default'),
            'Hashing' => config('hashing.driver'),
            'Redis' => config('redis.client'),
        ]));

        new Collection(static::$customDataResolvers)->each->__invoke();
    }

    /**
     * Display the application information.
     */
    protected function display(Collection $data): void
    {
        $this->option('json') ? $this->displayJson($data) : $this->displayDetail($data);
    }

    /**
     * Display the application information as a detail view.
     */
    protected function displayDetail(Collection $data): void
    {
        $data->each(function ($data, $section) {
            $this->newLine();

            $this->components->twoColumnDetail('  <fg=green;options=bold>'.$section.'</>');

            $data->pipe(fn ($data) => $section !== 'Environment' ? $data->sort() : $data)->each(function ($detail) {
                [$label, $value] = $detail;

                $this->components->twoColumnDetail($label, value($value, false));
            });
        });
    }

    /**
     * Display the application information as JSON.
     */
    protected function displayJson(Collection $data): void
    {
        $output = $data->flatMap(function ($data, $section) {
            return [
                new Stringable($section)->snake()->value() => $data->mapWithKeys(fn ($item, $key) => [
                    $this->toSearchKeyword($item[0]) => value($item[1], true),
                ]),
            ];
        });

        $this->output->writeln(strip_tags(json_encode($output)));
    }

    /**
     * Get the sections provided to the command.
     *
     * @return list<string>
     */
    protected function sections(): array
    {
        return new Collection(explode(',', $this->option('only') ?? ''))
            ->filter()
            ->map(fn ($only) => $this->toSearchKeyword($only))
            ->all();
    }

    /**
     * Format the given string for searching.
     */
    protected function toSearchKeyword(string $value): string
    {
        return new Stringable($value)->lower()->snake()->value();
    }

    /**
     * Flush the registered about data.
     */
    public static function flushState(): void
    {
        static::$data = [];

        static::$customDataResolvers = [];
    }

    /**
     * Materialize a function that formats a given value for CLI or JSON output.
     *
     * @param  (Closure(mixed):(mixed))|null  $console
     * @param  (Closure(mixed):(mixed))|null  $json
     * @return Closure(bool):mixed
     */
    public static function format(mixed $value, ?Closure $console = null, ?Closure $json = null): Closure
    {
        return function ($isJson) use ($value, $console, $json) {
            if ($isJson === true && $json instanceof Closure) {
                return value($json, $value);
            } elseif ($isJson === false && $console instanceof Closure) {
                return value($console, $value);
            }

            return value($value);
        };
    }

    /**
     * Add additional data to the output of the "about" command.
     *
     * Call from a package service provider `boot()` so discovered packages can
     * contribute sections (e.g. Integrated Circuits) without Core knowing them.
     *
     * @param  callable|array|string  $data
     */
    public static function add(string $section, callable|array|string $data, ?string $value = null): void
    {
        static::$customDataResolvers[] = fn () => static::addToSection($section, $data, $value);
    }

    /**
     * Add additional data to the output of the "about" command.
     *
     * @param  callable|array|string  $data
     */
    protected static function addToSection(string $section, callable|array|string $data, ?string $value = null): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                self::$data[$section][] = [$key, $value];
            }
        } elseif (is_callable($data) || (is_null($value) && class_exists($data))) {
            self::$data[$section][] = $data;
        } else {
            self::$data[$section][] = [$data, $value];
        }
    }
}
