<?php

namespace DeptOfScrapyardRobotics\Tests\Core;

use Fabricate\Config\Repository;
use Fabricate\Console\ConsoleProgram;
use Fabricate\Console\Prompts\DisabledMultiSelectPrompt;
use Fabricate\Core\Console\InstallFontsCommand;
use Fabricate\Core\Console\InstallGfxCommand;
use Fabricate\Core\Console\InstallGpioCommand;
use Fabricate\Core\Console\InstallSensorsCommand;
use Fabricate\Core\Enums\GfxInstallTarget;
use Fabricate\Core\Machine;
use Fabricate\Core\Providers\WorkshopServiceProvider;
use Fabricate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class InstallCommandsTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        ConsoleProgram::forgetBootstrappers();
        $this->basePath = sys_get_temp_dir().'/scrapyard-install-cmd-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem())->mkdir([
            $this->basePath.'/bootstrap/cache',
            $this->basePath.'/config',
        ]);
        $this->basePath = realpath($this->basePath) ?: $this->basePath;

        file_put_contents($this->basePath.'/composer.json', json_encode([
            'require' => new \stdClass,
        ], JSON_THROW_ON_ERROR));

        file_put_contents($this->basePath.'/config/gfx.php', <<<'PHP'
<?php

return [
    'rendering' => [
        'default' => 'phpdafruit',
        'engines' => [
            'phpdafruit' => [],
            'sdl3' => [],
            'glfw' => [],
        ],
    ],
];
PHP);

        file_put_contents($this->basePath.'/config/displays.php', <<<'PHP'
<?php

return [
    'main' => [
        'type' => 'embedded',
        'driver' => 'color',
        'circuit' => 'st7789',
        'renderer' => 'phpdafruit',
        'buffer' => 'dirty',
    ],
];
PHP);
    }

    protected function tearDown(): void
    {
        ConsoleProgram::forgetBootstrappers();
        Machine::setInstance(null);
        (new SymfonyFilesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testWorkshopRegistersInstallCommands(): void
    {
        $machine = $this->makeMachine();
        $machine->register(WorkshopServiceProvider::class);

        $this->assertInstanceOf(InstallFontsCommand::class, $machine->make(InstallFontsCommand::class));
        $this->assertInstanceOf(InstallGpioCommand::class, $machine->make(InstallGpioCommand::class));
        $this->assertInstanceOf(InstallSensorsCommand::class, $machine->make(InstallSensorsCommand::class));
        $this->assertInstanceOf(InstallGfxCommand::class, $machine->make(InstallGfxCommand::class));

        $program = (new ConsoleProgram($machine, $machine->make('events'), '0.6.0'))
            ->setContainerCommandLoader();

        $this->assertTrue($program->has('install:fonts'));
        $this->assertTrue($program->has('install:gpio'));
        $this->assertTrue($program->has('install:sensors'));
        $this->assertTrue($program->has('install:gfx'));
    }

    public function testInstallFontsRequiresPackageAndPublishesProvider(): void
    {
        [$status, $command, $output] = $this->runInstallCommand(new FakeInstallFontsCommand([
            'required' => ['scrapyard-io/autopen:^0.6.0'],
            'published' => ['ScrapyardIO\\Fonts\\AutopenServiceProvider'],
        ]));

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertSame(['scrapyard-io/autopen:^0.6.0'], $command->requiredPackages);
        $this->assertSame(['ScrapyardIO\\Fonts\\AutopenServiceProvider'], $command->publishedProviders);
        $this->assertStringContainsString('Autopen font scaffolding installed successfully.', $output);
    }

    public function testInstallGpioAndSensorsPublishExpectedProviders(): void
    {
        [$gpioStatus, $gpio] = $this->runInstallCommand(new FakeInstallGpioCommand([
            'required' => ['scrapyard-io/gpio-framework:^0.6.0'],
            'published' => ['GeneralPurposeIO\\Core\\GPIOServiceProvider'],
        ]));
        [$sensorsStatus, $sensors] = $this->runInstallCommand(new FakeInstallSensorsCommand([
            'required' => ['scrapyard-io/waveforms:^0.6.0'],
            'published' => ['ScrapyardIO\\Waveforms\\Core\\Providers\\WaveformsServiceProvider'],
        ]));

        $this->assertSame(Command::SUCCESS, $gpioStatus);
        $this->assertSame(Command::SUCCESS, $sensorsStatus);
        $this->assertSame(['scrapyard-io/gpio-framework:^0.6.0'], $gpio->requiredPackages);
        $this->assertSame(['scrapyard-io/waveforms:^0.6.0'], $sensors->requiredPackages);
    }

    public function testInstallCommandsFailWhenComposerRequireFails(): void
    {
        [$status, $command, $output] = $this->runInstallCommand(new FakeInstallFontsCommand([
            'requireResult' => false,
        ]));

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Unable to install [scrapyard-io/autopen].', $output);
        $this->assertSame([], $command->publishedProviders);
    }

    public function testInstallFontsIsIdempotentWhenPackagesAlreadyPresent(): void
    {
        [$status, $command, $output] = $this->runInstallCommand(new FakeInstallFontsCommand([
            'alreadyInstalled' => ['scrapyard-io/autopen'],
            'published' => ['ScrapyardIO\\Fonts\\AutopenServiceProvider'],
        ]));

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertSame([], $command->requiredPackages);
        $this->assertStringContainsString('Required Composer packages are already installed.', $output);
        $this->assertSame(['ScrapyardIO\\Fonts\\AutopenServiceProvider'], $command->publishedProviders);
    }

    public function testInstallGfxResolvesFlagSelectionsAndActivatesDefaultBackend(): void
    {
        [$status, $command] = $this->runInstallCommand(new FakeInstallGfxCommand([
            'required' => ['scrapyard-io/tubes:^0.6.0', 'microscrap/sdl3-gfx:^0.6.0'],
            'published' => ['ScrapyardIO\\Tubes\\Core\\Providers\\TubesServiceProvider'],
            'loadedExtensions' => ['sdl3'],
        ]), [
            '--tubes' => true,
            '--sdl3' => true,
            '--default' => 'sdl3',
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertSame(
            ['scrapyard-io/tubes:^0.6.0', 'microscrap/sdl3-gfx:^0.6.0'],
            $command->requiredPackages,
        );
        $this->assertSame(
            ['ScrapyardIO\\Tubes\\Core\\Providers\\TubesServiceProvider'],
            $command->publishedProviders,
        );

        $gfx = require $this->basePath.'/config/gfx.php';
        $displays = require $this->basePath.'/config/displays.php';

        $this->assertSame('sdl3', $gfx['rendering']['default']);
        $this->assertSame('windowed', $displays['main']['type']);
        $this->assertSame('sdl3', $displays['main']['driver']);
        $this->assertSame('sdl3', $displays['main']['renderer']);
        $this->assertSame('sdl3', $displays['main']['buffer']);
    }

    public function testInstallGfxRejectsMissingExtensionFlags(): void
    {
        [$status, $command, $output] = $this->runInstallCommand(new FakeInstallGfxCommand([
            'loadedExtensions' => [],
        ]), [
            '--sdl3' => true,
        ]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('ext-sdl3 is missing', $output);
        $this->assertSame([], $command->requiredPackages);
    }

    public function testInstallGfxLeavesExistingMainDisplayWithoutForce(): void
    {
        [$status, $command, $output] = $this->runInstallCommand(new FakeInstallGfxCommand([
            'required' => ['microscrap/glfw-gfx:^0.6.0'],
            'loadedExtensions' => ['glfw'],
        ]), [
            '--glfw' => true,
            '--default' => 'glfw',
        ]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('left unchanged', $output);

        $displays = require $this->basePath.'/config/displays.php';
        $this->assertSame('embedded', $displays['main']['type']);

        $gfx = require $this->basePath.'/config/gfx.php';
        $this->assertSame('glfw', $gfx['rendering']['default']);
    }

    public function testGfxInstallTargetMetadata(): void
    {
        $this->assertSame('scrapyard-io/tubes:^0.6.0', GfxInstallTarget::TUBES->packageConstraint());
        $this->assertSame('microscrap/sdl3-gfx:^0.6.0', GfxInstallTarget::SDL3->packageConstraint());
        $this->assertSame('microscrap/glfw-gfx:^0.6.0', GfxInstallTarget::GLFW->packageConstraint());
        $this->assertNull(GfxInstallTarget::TUBES->requiredExtension());
        $this->assertSame('sdl3', GfxInstallTarget::SDL3->requiredExtension());
        $this->assertTrue(GfxInstallTarget::SDL3->isDesktopBackend());
        $this->assertFalse(GfxInstallTarget::TUBES->isDesktopBackend());
    }

    public function testDisabledMultiSelectIgnoresDisabledOptions(): void
    {
        $prompt = new DisabledMultiSelectPrompt(
            label: 'Choose packages',
            options: [
                'tubes' => 'Tubes',
                'sdl3' => 'SDL3 GFX (ext-sdl3 is missing)',
                'glfw' => 'GLFW GFX',
            ],
            default: ['sdl3'],
            disabled: ['sdl3'],
        );

        $this->assertSame([], $prompt->value());
        $this->assertTrue($prompt->isDisabled('sdl3'));
        $this->assertFalse($prompt->isDisabled('tubes'));

        $highlight = new ReflectionMethod(DisabledMultiSelectPrompt::class, 'highlight');
        $highlight->invoke($prompt, 1);

        $toggle = new ReflectionMethod(DisabledMultiSelectPrompt::class, 'toggleHighlighted');
        $toggle->invoke($prompt);
        $this->assertSame([], $prompt->value());

        $highlight->invoke($prompt, 0);
        $toggle->invoke($prompt);
        $this->assertSame(['tubes'], $prompt->value());

        $toggleAll = new ReflectionMethod(DisabledMultiSelectPrompt::class, 'toggleAll');
        $toggleAll->invoke($prompt);
        $this->assertSame(['tubes', 'glfw'], $prompt->value());
    }

    /**
     * @return array{0: int, 1: FakeInstallCommandContract, 2: string}
     */
    private function runInstallCommand(FakeInstallCommandContract $command, array $options = []): array
    {
        $machine = $this->makeMachine();
        $command->setScrapyardIO($machine);

        $input = new ArrayInput($options, $command->getDefinition());
        $output = new BufferedOutput;
        $status = $command->run($input, $output);

        return [$status, $command, $output->fetch()];
    }

    private function makeMachine(): Machine
    {
        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository([]));
        $machine->instance('files', new Filesystem);

        return $machine;
    }
}

interface FakeInstallCommandContract
{
    public array $requiredPackages { get; set; }

    public array $publishedProviders { get; set; }

    public function setScrapyardIO(\Fabricate\Contracts\Core\Program $scrapyard_io): void;

    public function getDefinition(): \Symfony\Component\Console\Input\InputDefinition;

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int;
}

trait FakeInstallCommandHooks
{
    public array $requiredPackages = [];

    public array $publishedProviders = [];

    public function __construct(private array $config = [])
    {
        parent::__construct();
    }

    protected function requireComposerPackages(string $composer, array $packages): bool
    {
        $already = $this->config['alreadyInstalled'] ?? [];

        $packages = array_values(array_filter(
            $packages,
            function (string $package) use ($already): bool {
                $name = explode(':', $package, 2)[0];

                return ! in_array($name, $already, true);
            },
        ));

        if ($packages === []) {
            $this->components->info('Required Composer packages are already installed.');

            return true;
        }

        $this->requiredPackages = $packages;

        return $this->config['requireResult'] ?? true;
    }

    protected function publishInstalledProvider(string $provider, array $extraArguments = []): bool
    {
        $this->publishedProviders[] = $provider;

        return $this->config['publishResult'] ?? true;
    }
}

class FakeInstallFontsCommand extends InstallFontsCommand implements FakeInstallCommandContract
{
    use FakeInstallCommandHooks;
}

class FakeInstallGpioCommand extends InstallGpioCommand implements FakeInstallCommandContract
{
    use FakeInstallCommandHooks;
}

class FakeInstallSensorsCommand extends InstallSensorsCommand implements FakeInstallCommandContract
{
    use FakeInstallCommandHooks;
}

class FakeInstallGfxCommand extends InstallGfxCommand implements FakeInstallCommandContract
{
    use FakeInstallCommandHooks;

    protected function ensureExtensionsAvailable(array $targets): bool
    {
        foreach ($targets as $target) {
            $extension = $target->requiredExtension();

            if (is_null($extension)) {
                continue;
            }

            if (! in_array($extension, $this->config['loadedExtensions'] ?? [], true)) {
                $this->components->error(
                    "Unable to install [{$target->label()}]: ext-{$extension} is missing."
                );

                return false;
            }
        }

        return true;
    }

    protected function promptForTargets(): array
    {
        return [];
    }
}
