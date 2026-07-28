<?php

namespace Fabricate\Displays\Console;

use Fabricate\Console\Command;
use Fabricate\Contracts\Circuits\CircuitRegistry;
use Fabricate\Contracts\Displays\Interfaces\FullColorDisplay;
use Fabricate\Contracts\Displays\Interfaces\MonochromeDisplay;
use Fabricate\Contracts\Displays\Interfaces\PanelIC;
use Fabricate\Contracts\Sketches\SketchRegistry;
use Fabricate\Filesystem\Filesystem;
use Fabricate\Framebuffers\FramebufferManager;
use Fabricate\Rendering\RenderManager;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

#[AsCommand(name: 'config:main-display')]
class SetMainDisplayCommand extends Command
{
    protected ?string $signature = 'config:main-display
                    {display? : "none", a windowed driver key, or a PanelIC circuit key}
                    {--renderer= : Renderer engine for embedded PanelIC mains}
                    {--buffer= : Framebuffer strategy for embedded PanelIC mains}
                    {--force : Overwrite the existing main display configuration}';

    protected string $description = 'Set the main display in config/displays.php';

    public function handle(): int
    {
        $path = $this->scrapyard_io->configPath('displays.php');
        $files = new Filesystem;

        if (! $files->exists($path)) {
            $this->components->error("Missing configuration file [{$path}].");

            return self::FAILURE;
        }

        $config = $this->resolveMainConfig();

        if (is_null($config)) {
            return self::FAILURE;
        }

        if (! $this->option('force') && $this->mainAlreadyMatches($files->get($path), $config)) {
            $this->components->info('Main display configuration already matches the selected option.');
            $this->offerWelcome();

            return self::SUCCESS;
        }

        if (! $this->writeMainDisplay($files, $path, $config)) {
            $this->components->error('Unable to update the main display configuration.');

            return self::FAILURE;
        }

        $this->components->info($this->successMessage($config));
        $this->offerWelcome();

        return self::SUCCESS;
    }

    /**
     * @return array{type: string, driver?: string, circuit?: string, renderer?: string, buffer?: string}|null
     */
    protected function resolveMainConfig(): ?array
    {
        $choice = $this->resolveChoice();

        if (is_null($choice)) {
            $this->components->error('No display selection was provided.');

            return null;
        }

        if ($choice === 'none') {
            return ['type' => 'console'];
        }

        if ($this->isConfiguredWindowedDriver($choice)) {
            return [
                'type' => 'windowed',
                'driver' => $choice,
                'renderer' => $choice,
                'buffer' => $this->windowedBufferKeyFor($choice),
            ];
        }

        if ($this->isConfiguredPanelCircuit($choice)) {
            $driver = $this->embeddedDriverFor($choice);

            if (is_null($driver)) {
                $this->components->error(
                    "Unable to determine an embedded display driver for circuit [{$choice}]."
                );

                return null;
            }

            $renderer = $this->resolveRenderer();
            $buffer = $this->resolveBuffer();

            if (is_null($renderer) || is_null($buffer)) {
                return null;
            }

            return [
                'type' => 'embedded',
                'driver' => $driver,
                'circuit' => $choice,
                'renderer' => $renderer,
                'buffer' => $buffer,
            ];
        }

        $this->components->error(
            "[{$choice}] is not a configured windowed display or PanelIC circuit."
        );

        return null;
    }

    protected function resolveChoice(): ?string
    {
        $argument = $this->argument('display');

        if (! is_null($argument) && $argument !== '') {
            return (string) $argument;
        }

        $options = [
            'none' => 'None (console)',
        ];

        foreach ($this->windowedDrivers() as $driver) {
            $options[$driver] = "Windowed: {$driver}";
        }

        foreach ($this->panelCircuits() as $circuit => $label) {
            $options[$circuit] = $label;
        }

        return select(
            'Which display should be the main display?',
            $options,
        );
    }

    protected function resolveRenderer(): ?string
    {
        $option = $this->option('renderer');

        if (! is_null($option) && $option !== '') {
            $renderer = (string) $option;
            $available = $this->availableRenderers();

            if (! in_array($renderer, $available, true)) {
                $this->components->error(
                    "Renderer [{$renderer}] is not available. Choices: ".implode(', ', $available)
                );

                return null;
            }

            return $renderer;
        }

        $available = $this->availableRenderers();

        if ($available === []) {
            $this->components->error('No renderer engines are registered.');

            return null;
        }

        return select('Which renderer should the main display use?', $available);
    }

    protected function resolveBuffer(): ?string
    {
        $option = $this->option('buffer');

        if (! is_null($option) && $option !== '') {
            $buffer = (string) $option;
            $available = $this->availableBuffers();

            if (! in_array($buffer, $available, true)) {
                $this->components->error(
                    "Framebuffer [{$buffer}] is not available. Choices: ".implode(', ', $available)
                );

                return null;
            }

            return $buffer;
        }

        $available = $this->availableBuffers();

        if ($available === []) {
            $this->components->error('No framebuffer strategies are registered.');

            return null;
        }

        return select('Which framebuffer strategy should the main display use?', $available);
    }

    /**
     * @return array<int, string>
     */
    protected function windowedDrivers(): array
    {
        $windowed = $this->scrapyard_io['config']->get('displays.windowed', []);

        if (! is_array($windowed)) {
            return [];
        }

        return array_values(array_filter(
            array_keys($windowed),
            fn (mixed $key): bool => is_string($key) && $key !== '',
        ));
    }

    protected function isConfiguredWindowedDriver(string $driver): bool
    {
        return in_array($driver, $this->windowedDrivers(), true);
    }

    /**
     * PanelIC circuit keys that appear in config/circuits.php and are registered.
     *
     * @return array<string, string>
     */
    protected function panelCircuits(): array
    {
        if (! $this->scrapyard_io->bound('circuit')) {
            return [];
        }

        /** @var CircuitRegistry $registry */
        $registry = $this->scrapyard_io->make('circuit');
        $configured = $this->scrapyard_io['config']->get('circuits', []);

        if (! is_array($configured)) {
            return [];
        }

        $options = [];

        foreach ($registry->listCircuits() as $name => $class) {
            if (! is_string($name) || ! is_string($class) || ! array_key_exists($name, $configured)) {
                continue;
            }

            if (! $this->classImplements($class, PanelIC::class)) {
                continue;
            }

            $driver = $this->embeddedDriverForClass($class);
            $suffix = is_null($driver) ? 'embedded' : $driver;
            $options[$name] = "PanelIC: {$name} ({$suffix})";
        }

        ksort($options);

        return $options;
    }

    protected function isConfiguredPanelCircuit(string $circuit): bool
    {
        return array_key_exists($circuit, $this->panelCircuits());
    }

    protected function embeddedDriverFor(string $circuit): ?string
    {
        if (! $this->scrapyard_io->bound('circuit')) {
            return null;
        }

        /** @var CircuitRegistry $registry */
        $registry = $this->scrapyard_io->make('circuit');
        $circuits = $registry->listCircuits();
        $class = $circuits[$circuit] ?? null;

        if (! is_string($class)) {
            return null;
        }

        return $this->embeddedDriverForClass($class);
    }

    protected function embeddedDriverForClass(string $class): ?string
    {
        if ($this->classImplements($class, FullColorDisplay::class)) {
            return 'color';
        }

        if ($this->classImplements($class, MonochromeDisplay::class)) {
            return 'monochrome';
        }

        return null;
    }

    protected function classImplements(string $class, string $interface): bool
    {
        if (! class_exists($class) && ! interface_exists($class)) {
            return false;
        }

        try {
            return (new ReflectionClass($class))->implementsInterface($interface);
        } catch (\ReflectionException) {
            return false;
        }
    }

    protected function windowedBufferKeyFor(string $driver): string
    {
        return match ($driver) {
            'glfw' => 'glfw-ogl',
            default => $driver,
        };
    }

    /**
     * @return array<int, string>
     */
    protected function availableRenderers(): array
    {
        if (! $this->scrapyard_io->bound(RenderManager::class) && ! $this->scrapyard_io->bound('gfx')) {
            return ['phpdafruit'];
        }

        /** @var RenderManager $manager */
        $manager = $this->scrapyard_io->bound('gfx')
            ? $this->scrapyard_io->make('gfx')
            : $this->scrapyard_io->make(RenderManager::class);

        return $manager->listRenderers();
    }

    /**
     * @return array<int, string>
     */
    protected function availableBuffers(): array
    {
        if (! $this->scrapyard_io->bound(FramebufferManager::class) && ! $this->scrapyard_io->bound('framebuffer')) {
            return ['full', 'dirty', 'page'];
        }

        /** @var FramebufferManager $manager */
        $manager = $this->scrapyard_io->bound('framebuffer')
            ? $this->scrapyard_io->make('framebuffer')
            : $this->scrapyard_io->make(FramebufferManager::class);

        return $manager->listFramebuffers();
    }

    /**
     * @param  array{type: string, driver?: string, circuit?: string, renderer?: string, buffer?: string}  $config
     */
    protected function writeMainDisplay(Filesystem $files, string $path, array $config): bool
    {
        $contents = $files->get($path);
        $mainBlock = $this->mainBlockSnippet($config);
        $updated = $this->replaceActiveMainBlock($contents, $mainBlock);

        if (is_null($updated)) {
            $updated = preg_replace('/return\\s*\\[/', "return [\n".$mainBlock, $contents, 1);
        }

        if (is_null($updated) || $updated === $contents) {
            return false;
        }

        $files->put($path, $updated);

        return true;
    }

    /**
     * Replace the first uncommented `main` array assignment.
     */
    protected function replaceActiveMainBlock(string $contents, string $mainBlock): ?string
    {
        if (preg_match_all(
            "/['\"]main['\"]\\s*=>\\s*\\[(?:[^\\[\\]]*(?:\\[[^\\[\\]]*\\][^\\[\\]]*)*)*\\],?/",
            $contents,
            $matches,
            PREG_OFFSET_CAPTURE,
        ) === 0 || $matches[0] === []) {
            return null;
        }

        foreach ($matches[0] as [$match, $offset]) {
            if ($this->isInsideBlockComment($contents, (int) $offset)) {
                continue;
            }

            return substr($contents, 0, (int) $offset)
                .trim($mainBlock)
                .substr($contents, (int) $offset + strlen($match));
        }

        return null;
    }

    protected function isInsideBlockComment(string $contents, int $offset): bool
    {
        $before = substr($contents, 0, $offset);
        $open = strrpos($before, '/*');

        if ($open === false) {
            return false;
        }

        $close = strrpos($before, '*/');

        return $close === false || $close < $open;
    }

    /**
     * @param  array{type: string, driver?: string, circuit?: string, renderer?: string, buffer?: string}  $config
     */
    protected function mainBlockSnippet(array $config): string
    {
        if ($config['type'] === 'console') {
            return <<<'PHP'
    'main' => [
        'type' => 'console',
    ],
PHP;
        }

        if ($config['type'] === 'windowed') {
            return <<<PHP
    'main' => [
        'type' => 'windowed',
        'driver' => '{$config['driver']}',
        'renderer' => '{$config['renderer']}',
        'buffer' => '{$config['buffer']}',
    ],
PHP;
        }

        return <<<PHP
    'main' => [
        'type' => 'embedded',
        'driver' => '{$config['driver']}',
        'circuit' => '{$config['circuit']}',
        'renderer' => '{$config['renderer']}',
        'buffer' => '{$config['buffer']}',
    ],
PHP;
    }

    /**
     * @param  array{type: string, driver?: string, circuit?: string, renderer?: string, buffer?: string}  $config
     */
    protected function mainAlreadyMatches(string $contents, array $config): bool
    {
        if ($config['type'] === 'console') {
            return (bool) preg_match(
                "/['\"]main['\"]\\s*=>\\s*\\[[^\\]]*['\"]type['\"]\\s*=>\\s*['\"]console['\"]/",
                $contents,
            );
        }

        if ($config['type'] === 'windowed') {
            return str_contains($contents, "'type' => 'windowed'")
                && str_contains($contents, "'driver' => '{$config['driver']}'")
                && str_contains($contents, "'renderer' => '{$config['renderer']}'")
                && str_contains($contents, "'buffer' => '{$config['buffer']}'");
        }

        return str_contains($contents, "'type' => 'embedded'")
            && str_contains($contents, "'driver' => '{$config['driver']}'")
            && str_contains($contents, "'circuit' => '{$config['circuit']}'")
            && str_contains($contents, "'renderer' => '{$config['renderer']}'")
            && str_contains($contents, "'buffer' => '{$config['buffer']}'");
    }

    /**
     * @param  array{type: string, driver?: string, circuit?: string, renderer?: string, buffer?: string}  $config
     */
    protected function successMessage(array $config): string
    {
        return match ($config['type']) {
            'console' => 'Set main display to console.',
            'windowed' => "Set main display to windowed [{$config['driver']}].",
            default => "Set main display to embedded circuit [{$config['circuit']}].",
        };
    }

    protected function offerWelcome(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        $application = $this->getApplication();
        $hasWelcomeCommand = ! is_null($application) && $application->has('welcome');
        $hasWelcomeSketch = $this->hasWelcomeSketch();

        if (! $hasWelcomeCommand && ! $hasWelcomeSketch) {
            return;
        }

        $this->components->warn(
            'Review your windowed or circuit resolution settings in config before testing the display.'
        );

        if (! confirm('Would you like to run the welcome sketch now?', default: true)) {
            return;
        }

        if ($hasWelcomeCommand) {
            $this->call('welcome');

            return;
        }

        $this->call('sketch', ['name' => 'welcome']);
    }

    protected function hasWelcomeSketch(): bool
    {
        if (! $this->scrapyard_io->bound(SketchRegistry::class) && ! $this->scrapyard_io->bound('sketch')) {
            return false;
        }

        /** @var SketchRegistry $registry */
        $registry = $this->scrapyard_io->bound('sketch')
            ? $this->scrapyard_io->make('sketch')
            : $this->scrapyard_io->make(SketchRegistry::class);

        return $registry->has('welcome');
    }
}
