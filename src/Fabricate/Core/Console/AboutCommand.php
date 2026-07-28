<?php

namespace Fabricate\Core\Console;

use Closure;
use Fabricate\Circuits\CircuitRegistry;
use Fabricate\Console\Command;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Circuits\Attributes\IntegratedCircuit;
use Fabricate\Displays\DisplayRegistry;
use Fabricate\Fonts\FontRegistry;
use Fabricate\Framebuffers\FramebufferManager;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Composer;
use Fabricate\NutsAndBolts\Str;
use Fabricate\NutsAndBolts\Stringable;
use Fabricate\Rendering\RenderManager;
use Fabricate\Sensors\SensorRegistry;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'about')]
class AboutCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var ?string
     */
    protected ?string $signature = 'about {--only= : The section to display}
                {--json : Output the information as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Display basic information about your application';

    /**
     * The Composer instance.
     *
     * @var Composer
     */
    protected Composer $composer;

    /**
     * The data to display.
     *
     * @var array
     */
    protected static array $data = [];

    /**
     * The registered callables that add custom data to the command output.
     *
     * @var array
     */
    protected static array $customDataResolvers = [];

    /**
     * Create a new command instance.
     *
     * @param  Composer  $composer
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return int
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
            ->sortBy(function ($data, $key) {
                $index = array_search($key, ['Environment', 'Cache', 'Drivers']);

                return $index === false ? 99 : $index;
            })
            ->filter(function ($data, $key) {
                return $this->option('only') ? in_array($this->toSearchKeyword($key), $this->sections()) : true;
            })
            ->pipe(fn ($data) => $this->display($data));

        $this->newLine();

        return 0;
    }

    /**
     * Gather information about the application.
     *
     * @return void
     */
    protected function gatherApplicationInformation(): void
    {
        self::$data = [];

        $formatEnabledStatus = fn ($value) => $value ? '<fg=yellow;options=bold>ENABLED</>' : 'OFF';
        $formatCachedStatus = fn ($value) => $value ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>';
        $formatStorageLinkedStatus = fn ($value) => $value ? '<fg=green;options=bold>LINKED</>' : '<fg=yellow;options=bold>NOT LINKED</>';

        static::addToSection('Environment', fn () => [
            'Application Name' => config('machine.name'),
            'ScrapyardIO Version' => $this->scrapyard_io->version(),
            'PHP Version' => phpversion(),
            'Composer Version' => $this->composer->getVersion() ?? '<fg=yellow;options=bold>-</>',
            'Environment' => $this->scrapyard_io->environment(),
            'Debug Mode' => static::format(config('machine.debug'), console: $formatEnabledStatus),
            'Timezone' => config('machine.timezone'),
            'Locale' => config('machine.locale'),
        ]);

        static::addToSection('Cache', fn () => [
            'Config' => static::format($this->scrapyard_io->configurationIsCached(), console: $formatCachedStatus),
            'Events' => static::format($this->scrapyard_io->eventsAreCached(), console: $formatCachedStatus),
            // @todo - add a space for Dependant CLibs and Wrapper Packages.
        ]);

        static::addToSection('Drivers', fn () => array_filter([
            //'Broadcasting' => config('broadcasting.default'),
            'Cache' => function ($json) {
                $cacheStore = config('cache.default');

                if (config('cache.stores.'.$cacheStore.'.driver') === 'failover') {
                    $secondary = new Collection(config('cache.stores.'.$cacheStore.'.stores'));

                    return value(static::format(
                        value: $cacheStore,
                        console: fn ($value) => '<fg=yellow;options=bold>'.$value.'</> <fg=gray;options=bold>/</> '.$secondary->implode(', '),
                        json: fn () => $secondary->all(),
                    ), $json);
                }

                return $cacheStore;
            },
            //'Database' => config('database.default'),
            'Logs' => function ($json) {
                $logChannel = config('logging.default');

                if (config('logging.channels.'.$logChannel.'.driver') === 'stack') {
                    $secondary = new Collection(config('logging.channels.'.$logChannel.'.channels'));

                    return value(static::format(
                        value: $logChannel,
                        console: fn ($value) => '<fg=yellow;options=bold>'.$value.'</> <fg=gray;options=bold>/</> '.$secondary->implode(', '),
                        json: fn () => $secondary->all(),
                    ), $json);
                } else {
                    $logs = $logChannel;
                }

                return $logs;
            },
            /*'Mail' => function ($json) {
                $mailMailer = config('mail.default');

                if (in_array(config('mail.mailers.'.$mailMailer.'.transport'), ['failover', 'roundrobin'])) {
                    $secondary = new Collection(config('mail.mailers.'.$mailMailer.'.mailers'));

                    return value(static::format(
                        value: $mailMailer,
                        console: fn ($value) => '<fg=yellow;options=bold>'.$value.'</> <fg=gray;options=bold>/</> '.$secondary->implode(', '),
                        json: fn () => $secondary->all(),
                    ), $json);
                }

                return $mailMailer;
            },*/
            'Queue' => function ($json) {
                $queueConnection = config('queue.default');

                if (config('queue.connections.'.$queueConnection.'.driver') === 'failover') {
                    $secondary = new Collection(config('queue.connections.'.$queueConnection.'.connections'));

                    return value(static::format(
                        value: $queueConnection,
                        console: fn ($value) => '<fg=yellow;options=bold>'.$value.'</> <fg=gray;options=bold>/</> '.$secondary->implode(', '),
                        json: fn () => $secondary->all(),
                    ), $json);
                }

                return $queueConnection;
            },
        ]));

        static::addToSection('Integrated Circuits', fn () => $this->integratedCircuitInformation());

        static::addToSection('Sensors', fn () => $this->sensorInformation());

        static::addToSection('Displays', fn () => $this->displayInformation());

        static::addToSection('Framebuffers', fn () => $this->framebufferInformation());

        static::addToSection('Renderers', fn () => $this->rendererInformation());

        static::addToSection('Installed Fonts', fn () => $this->fontInformation());

        static::addToSection('Storage', fn () => [
            ...$this->determineStoragePathLinkStatus($formatStorageLinkedStatus),
        ]);

        new Collection(static::$customDataResolvers)->each->__invoke();
    }

    /**
     * @return array<string, mixed>
     */
    protected function integratedCircuitInformation(): array
    {
        /** @var CircuitRegistry $registry */
        $registry = $this->scrapyard_io->make('circuit');
        $circuits = $registry->listCircuits();

        if ($circuits === []) {
            return ['Installed' => 'None'];
        }

        return new Collection($circuits)
            ->sortKeys()
            ->map(function (string $class) {
                $attributes = new ReflectionClass($class)->getAttributes(IntegratedCircuit::class);
                $protocols = $attributes === []
                    ? []
                    : $attributes[0]->newInstance()->protocols;

                return static::format(
                    $protocols,
                    console: fn (array $protocols) => $protocols === [] ? 'None declared' : implode(', ', $protocols),
                );
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sensorInformation(): array
    {
        /** @var SensorRegistry $registry */
        $registry = $this->scrapyard_io->make('sensor');
        $sensors = $registry->listSensors();

        if ($sensors === []) {
            return ['Installed' => 'None'];
        }

        return new Collection($sensors)
            ->sortKeys()
            ->map(fn (string $class) => new ReflectionClass($class)->getShortName())
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function displayInformation(): array
    {
        /** @var DisplayRegistry $registry */
        $registry = $this->scrapyard_io->make('display');
        $displays = $registry->listDisplays();

        return [
            'Embedded' => $this->formatRegistrationNames($displays['embedded'] ?? []),
            'Windowed' => $this->formatRegistrationNames($displays['windowed'] ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function framebufferInformation(): array
    {
        /** @var FramebufferManager $manager */
        $manager = $this->scrapyard_io->make('framebuffer');

        return ['Installed' => $this->formatNames($manager->listFramebuffers())];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rendererInformation(): array
    {
        /** @var RenderManager $manager */
        $manager = $this->scrapyard_io->make('gfx');

        return ['Installed' => $this->formatNames($manager->listRenderers())];
    }

    /**
     * @return array<string, mixed>
     */
    protected function fontInformation(): array
    {
        /** @var FontRegistry $registry */
        $registry = $this->scrapyard_io->make('font');
        $fonts = $registry->listFonts();

        if ($fonts === []) {
            return ['Installed' => 'None'];
        }

        return new Collection($fonts)
            ->sortKeys()
            ->map(fn (string $class) => new ReflectionClass($class)->getShortName())
            ->all();
    }

    /**
     * @param  array<string, class-string>  $registrations
     */
    protected function formatRegistrationNames(array $registrations): Closure
    {
        return $this->formatNames(array_keys($registrations));
    }

    /**
     * @param  array<int, string>  $names
     */
    protected function formatNames(array $names): Closure
    {
        sort($names);

        return static::format(
            $names,
            console: fn (array $names) => $names === [] ? 'None' : implode(', ', $names),
        );
    }

    /**
     * Determine storage symbolic links status.
     *
     * @param callable $formatStorageLinkedStatus
     * @return array<string,mixed>
     * @throws CircularDependencyException
     */
    protected function determineStoragePathLinkStatus(callable $formatStorageLinkedStatus): array
    {
        return new Collection(config('filesystems.links', []))
            ->mapWithKeys(function ($target, $link) use ($formatStorageLinkedStatus) {
                $path = Str::replace(storage_path(), '', $link);

                return [storage_path($path) => static::format(file_exists($link), console: $formatStorageLinkedStatus)];
            })
            ->toArray();
    }

    /**
     * Display the application information.
     *
     * @param Collection $data
     * @return void
     */
    protected function display(Collection $data): void
    {
        $this->option('json') ? $this->displayJson($data) : $this->displayDetail($data);
    }

    /**
     * Display the application information as a detail view.
     *
     * @param Collection $data
     * @return void
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
     *
     * @param  Collection  $data
     * @return void
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
     * Determine whether the given directory has PHP files.
     *
     * @param  string  $path
     * @return bool
     */
    protected function hasPhpFiles(string $path): bool
    {
        return count(glob($path.'/*.php')) > 0;
    }

    /**
     * Get the sections provided to the command.
     *
     * @return array
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
     *
     * @param  string  $value
     * @return string
     */
    protected function toSearchKeyword(string $value): string
    {
        return new Stringable($value)->lower()->snake()->value();
    }

    /**
     * Flush the registered about data.
     *
     * @return void
     */
    public static function flushState(): void
    {
        static::$data = [];

        static::$customDataResolvers = [];
    }

    /**
     * Materialize a function that formats a given value for CLI or JSON output.
     *
     * @param  mixed  $value
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
     * @param  string  $section
     * @param callable|array|string $data
     * @param  string|null  $value
     * @return void
     */
    public static function add(string $section, callable|array|string $data, ?string $value = null): void
    {
        static::$customDataResolvers[] = fn () => static::addToSection($section, $data, $value);
    }

    /**
     * Add additional data to the output of the "about" command.
     *
     * @param  string  $section
     * @param callable|array|string $data
     * @param  string|null  $value
     * @return void
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