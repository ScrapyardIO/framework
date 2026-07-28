<?php

namespace DeptOfScrapyardRobotics\Tests\Core;

use Fabricate\Console\ConsoleProgram;
use Fabricate\Core\Console\PackageDiscoverCommand;
use Fabricate\Core\Machine;
use Fabricate\Core\Providers\ConsoleSupportServiceProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;

class PackageDiscoverCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        ConsoleProgram::forgetBootstrappers();
        $this->basePath = sys_get_temp_dir().'/scrapyard-discover-'.bin2hex(random_bytes(8));
        (new Filesystem())->mkdir([
            $this->basePath.'/bootstrap/cache',
            $this->basePath.'/vendor/composer',
        ]);
    }

    protected function tearDown(): void
    {
        ConsoleProgram::forgetBootstrappers();
        (new Filesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testConsoleSupportIsEnabledByDefault(): void
    {
        $this->assertContains(
            ConsoleSupportServiceProvider::class,
            ServiceProvider::defaultProviders()->toArray(),
        );
    }

    public function testPackageDiscoverRebuildsTheCachedManifest(): void
    {
        file_put_contents($this->basePath.'/composer.json', '{"extra":{"scrapyard-io":{"dont-discover":[]}}}');
        file_put_contents($this->basePath.'/vendor/composer/installed.json', json_encode([
            'packages' => [
                [
                    'name' => 'vendor/discovered',
                    'extra' => [
                        'scrapyard-io' => [
                            'providers' => ['Vendor\\Discovered\\ServiceProvider'],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $machine = new Machine($this->basePath);
        $machine->register(ConsoleSupportServiceProvider::class);
        $program = (new ConsoleProgram($machine, $machine->make('events'), '0.6.0'))
            ->setContainerCommandLoader();

        $status = $program->call('package:discover');
        $output = $program->output();

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('Discovering packages', $output);
        $this->assertStringContainsString('vendor/discovered', $output);
        $this->assertFileExists($this->basePath.'/bootstrap/cache/packages.php');
        $this->assertSame([
            'vendor/discovered' => [
                'providers' => ['Vendor\\Discovered\\ServiceProvider'],
            ],
        ], require $this->basePath.'/bootstrap/cache/packages.php');
        $this->assertInstanceOf(
            PackageDiscoverCommand::class,
            $machine->make(PackageDiscoverCommand::class),
        );
    }
}
