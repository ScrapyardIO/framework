<?php

namespace DeptOfScrapyardRobotics\Tests\Core;

use DeptOfScrapyardRobotics\Displays\GLFW\Console\ConfigureGlfwDisplayCommand;
use DeptOfScrapyardRobotics\Displays\SDL3\Console\ConfigureSdl3DisplayCommand;
use Fabricate\Config\Repository;
use Fabricate\Console\ConsoleProgram;
use Fabricate\Core\Machine;
use Fabricate\Filesystem\Filesystem;
use Microscrap\GFX\GLFW\Console\InstallGlfwDisplayCommand;
use Microscrap\GFX\SDL3\Console\InstallSdl3DisplayCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class DisplayConfigInstallCommandsTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        ConsoleProgram::forgetBootstrappers();
        $this->basePath = sys_get_temp_dir().'/scrapyard-display-cmd-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem())->mkdir([
            $this->basePath.'/bootstrap/cache',
            $this->basePath.'/config',
        ]);
        $this->basePath = realpath($this->basePath) ?: $this->basePath;

        file_put_contents($this->basePath.'/composer.json', json_encode([
            'require' => [
                'microscrap/sdl3-gfx' => '^0.6.0',
                'microscrap/glfw-gfx' => '^0.6.0',
            ],
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        ConsoleProgram::forgetBootstrappers();
        Machine::setInstance(null);
        (new SymfonyFilesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testConfigSdl3DisplayIsHiddenWhenWindowedEntryExists(): void
    {
        $machine = $this->makeMachine([
            'displays' => [
                'windowed' => [
                    'sdl3' => ['width' => 1024],
                ],
            ],
        ]);

        $command = new ConfigureSdl3DisplayCommand;
        $command->setScrapyardIO($machine);

        $this->assertTrue($command->isHidden());
    }

    public function testConfigSdl3DisplayIsVisibleAndWritesWindowedEntry(): void
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

        $command = new ConfigureSdl3DisplayCommand;
        $command->setScrapyardIO($machine);

        $this->assertFalse($command->isHidden());

        $input = new ArrayInput([]);
        $input->setInteractive(false);
        $status = $command->run($input, new BufferedOutput);

        $this->assertSame(Command::SUCCESS, $status);
        $contents = file_get_contents($this->basePath.'/config/displays.php');
        $this->assertStringContainsString("'windowed'", $contents);
        $this->assertStringContainsString("'sdl3'", $contents);
        $this->assertStringContainsString("'scale_factor' => 1", $contents);
    }

    public function testConfigGlfwDisplayWritesDefaultEntryIntoExistingWindowedArray(): void
    {
        file_put_contents($this->basePath.'/config/displays.php', <<<'PHP'
<?php

return [
    'windowed' => [
        'sdl3' => [
            'width' => 800,
            'height' => 600,
            'title' => env('APP_NAME'),
            'boot_now' => true,
        ],
    ],
];
PHP);

        $machine = $this->makeMachine([
            'displays' => [
                'windowed' => [
                    'sdl3' => ['width' => 800],
                ],
            ],
        ]);

        $command = new ConfigureGlfwDisplayCommand;
        $command->setScrapyardIO($machine);

        $this->assertFalse($command->isHidden());

        $input = new ArrayInput([]);
        $input->setInteractive(false);
        $status = $command->run($input, new BufferedOutput);

        $this->assertSame(Command::SUCCESS, $status);
        $contents = file_get_contents($this->basePath.'/config/displays.php');
        $this->assertStringContainsString("'glfw'", $contents);
        $this->assertStringContainsString("'sdl3'", $contents);
    }

    public function testInstallDisplayCommandsHideWhenComposerAlreadyRequiresPackages(): void
    {
        file_put_contents($this->basePath.'/composer.json', json_encode([
            'require' => [
                'dept-of-scrapyard-robotics/sdl3-display' => '^0.6.0',
                'dept-of-scrapyard-robotics/glfw-display' => '^0.6.0',
            ],
        ], JSON_THROW_ON_ERROR));

        $machine = $this->makeMachine([]);

        $sdl3 = new InstallSdl3DisplayCommand;
        $sdl3->setScrapyardIO($machine);
        $glfw = new InstallGlfwDisplayCommand;
        $glfw->setScrapyardIO($machine);

        $this->assertTrue($sdl3->isHidden());
        $this->assertTrue($glfw->isHidden());
    }

    public function testInstallDisplayCommandsAreVisibleWhenDisplayPackagesMissing(): void
    {
        $machine = $this->makeMachine([]);

        $sdl3 = new InstallSdl3DisplayCommand;
        $sdl3->setScrapyardIO($machine);
        $glfw = new InstallGlfwDisplayCommand;
        $glfw->setScrapyardIO($machine);

        $this->assertFalse($sdl3->isHidden());
        $this->assertFalse($glfw->isHidden());
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
}
