<?php

namespace DeptOfScrapyardRobotics\Tests\Core;

use Fabricate\Circuits\CircuitRegistry;
use Fabricate\Config\Repository;
use Fabricate\Console\ConsoleProgram;
use Fabricate\Core\Machine;
use Fabricate\Displays\Console\SetMainDisplayCommand;
use Fabricate\Filesystem\Filesystem;
use Fabricate\Framebuffers\FramebufferManager;
use Fabricate\Rendering\RenderManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class SetMainDisplayCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        ConsoleProgram::forgetBootstrappers();
        $this->basePath = sys_get_temp_dir().'/scrapyard-main-display-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem())->mkdir([
            $this->basePath.'/bootstrap/cache',
            $this->basePath.'/config',
        ]);
        $this->basePath = realpath($this->basePath) ?: $this->basePath;
    }

    protected function tearDown(): void
    {
        ConsoleProgram::forgetBootstrappers();
        Machine::setInstance(null);
        (new SymfonyFilesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testSetsConsoleMainDisplay(): void
    {
        file_put_contents($this->basePath.'/config/displays.php', <<<'PHP'
<?php

return [
    'main' => [
        'type' => 'windowed',
        'driver' => 'sdl3',
        'renderer' => 'sdl3',
        'buffer' => 'sdl3',
    ],
];
PHP);

        $machine = $this->makeMachine([
            'displays' => [
                'main' => ['type' => 'windowed', 'driver' => 'sdl3'],
                'windowed' => ['sdl3' => ['width' => 1024]],
            ],
        ]);

        $status = $this->runCommand($machine, [
            'display' => 'none',
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $status);
        $contents = file_get_contents($this->basePath.'/config/displays.php');
        $this->assertStringContainsString("'type' => 'console'", $contents);
        $this->assertStringNotContainsString("'driver' => 'sdl3'", $contents);
    }

    public function testSetsWindowedSdl3MainDisplay(): void
    {
        file_put_contents($this->basePath.'/config/displays.php', <<<'PHP'
<?php

return [
    'main' => [
        'type' => 'console',
    ],
    'windowed' => [
        'sdl3' => [
            'width' => 1024,
            'height' => 768,
        ],
    ],
];
PHP);

        $machine = $this->makeMachine([
            'displays' => [
                'main' => ['type' => 'console'],
                'windowed' => ['sdl3' => ['width' => 1024]],
            ],
        ]);

        $status = $this->runCommand($machine, [
            'display' => 'sdl3',
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $status);
        $contents = file_get_contents($this->basePath.'/config/displays.php');
        $this->assertStringContainsString("'type' => 'windowed'", $contents);
        $this->assertStringContainsString("'driver' => 'sdl3'", $contents);
        $this->assertStringContainsString("'renderer' => 'sdl3'", $contents);
        $this->assertStringContainsString("'buffer' => 'sdl3'", $contents);
    }

    public function testSetsEmbeddedPanelIcMainDisplay(): void
    {
        file_put_contents($this->basePath.'/config/displays.php', <<<'PHP'
<?php

return [
    'main' => [
        'type' => 'console',
    ],
];
PHP);

        $machine = $this->makeMachine([
            'displays' => [
                'main' => ['type' => 'console'],
            ],
            'circuits' => [
                'st7789' => [
                    'protocol' => 'spi',
                    'params' => [],
                ],
            ],
        ]);

        $registry = new CircuitRegistry;
        $registry->addCircuit('st7789', \DeptOfScrapyardRobotics\Displays\ST77xx\ST7789\ST7789::class);
        $machine->instance('circuit', $registry);
        $machine->instance('gfx', new RenderManager($machine));
        $machine->instance('framebuffer', new FramebufferManager);

        $status = $this->runCommand($machine, [
            'display' => 'st7789',
            '--renderer' => 'phpdafruit',
            '--buffer' => 'dirty',
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $status);
        $contents = file_get_contents($this->basePath.'/config/displays.php');
        $this->assertStringContainsString("'type' => 'embedded'", $contents);
        $this->assertStringContainsString("'driver' => 'color'", $contents);
        $this->assertStringContainsString("'circuit' => 'st7789'", $contents);
        $this->assertStringContainsString("'renderer' => 'phpdafruit'", $contents);
        $this->assertStringContainsString("'buffer' => 'dirty'", $contents);
    }

    public function testRejectsUnknownDisplayChoice(): void
    {
        file_put_contents($this->basePath.'/config/displays.php', <<<'PHP'
<?php

return [
    'main' => [
        'type' => 'console',
    ],
];
PHP);

        $machine = $this->makeMachine([
            'displays' => [
                'main' => ['type' => 'console'],
            ],
        ]);

        $status = $this->runCommand($machine, [
            'display' => 'not-a-display',
            '--force' => true,
        ]);

        $this->assertSame(Command::FAILURE, $status);
    }

    public function testSkipsCommentedMainBlocksWhenReplacing(): void
    {
        file_put_contents($this->basePath.'/config/displays.php', <<<'PHP'
<?php

return [
    /*'main' => [
        'type' => 'windowed',
        'driver' => 'sdl3',
        'renderer' => 'sdl3',
        'buffer' => 'sdl3'
    ],*/
    'main' => [
        'type' => 'console',
    ],
];
PHP);

        $machine = $this->makeMachine([
            'displays' => [
                'main' => ['type' => 'console'],
                'windowed' => ['sdl3' => ['width' => 1024]],
            ],
        ]);

        $status = $this->runCommand($machine, [
            'display' => 'sdl3',
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $status);
        $contents = file_get_contents($this->basePath.'/config/displays.php');
        $this->assertMatchesRegularExpression("/\\/\\*'main' => \\[/", $contents);
        $this->assertStringContainsString("'driver' => 'sdl3'", $contents);
        $this->assertSame(1, preg_match_all("/^\\s*'main' => \\[/m", $contents));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeMachine(array $config): Machine
    {
        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository($config));
        $machine->instance('files', new Filesystem);

        return $machine;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function runCommand(Machine $machine, array $arguments): int
    {
        $command = new SetMainDisplayCommand;
        $command->setScrapyardIO($machine);

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $command->run($input, new BufferedOutput);
    }
}
