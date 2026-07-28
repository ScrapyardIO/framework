<?php

namespace DeptOfScrapyardRobotics\Tests\Core;

use Fabricate\Core\PackageManifest;
use Fabricate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class PackageManifestTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/scrapyard-manifest-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem())->mkdir([
            $this->basePath.'/vendor/composer',
            $this->basePath.'/bootstrap/cache',
        ]);
    }

    protected function tearDown(): void
    {
        (new SymfonyFilesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testItBuildsPackageDiscoveryConfigurationFromInstalledPackages(): void
    {
        file_put_contents($this->basePath.'/vendor/composer/installed.json', json_encode([
            'packages' => [
                [
                    'name' => 'vendor/discovery',
                    'extra' => [
                        'scrapyard-io' => [
                            'providers' => ['Vendor\\Discovery\\Provider'],
                            'aliases' => ['Discovery' => 'Vendor\\Discovery\\Alias'],
                            'dont-discover' => ['vendor/ignored-by-package'],
                        ],
                    ],
                ],
                [
                    'name' => 'vendor/ignored-by-package',
                    'extra' => [
                        'scrapyard-io' => [
                            'providers' => ['Ignored\\Provider'],
                        ],
                    ],
                ],
                [
                    'name' => 'vendor/no-configuration',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        $manifest = $this->manifest();

        $manifest->build();

        $this->assertSame(['Vendor\\Discovery\\Provider'], $manifest->providers());
        $this->assertSame(['Discovery' => 'Vendor\\Discovery\\Alias'], $manifest->aliases());
        $this->assertFileExists($this->basePath.'/bootstrap/cache/packages.php');
    }

    public function testRootComposerConfigurationCanExcludePackages(): void
    {
        file_put_contents($this->basePath.'/composer.json', json_encode([
            'extra' => [
                'scrapyard-io' => [
                    'dont-discover' => ['vendor/ignored'],
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($this->basePath.'/vendor/composer/installed.json', json_encode([
            [
                'name' => 'vendor/kept',
                'extra' => ['scrapyard-io' => ['providers' => ['Kept\\Provider']]],
            ],
            [
                'name' => 'vendor/ignored',
                'extra' => ['scrapyard-io' => ['providers' => ['Ignored\\Provider']]],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(['Kept\\Provider'], $this->manifest()->providers());
    }

    public function testWildcardExclusionDisablesAllPackageDiscovery(): void
    {
        file_put_contents($this->basePath.'/composer.json', json_encode([
            'extra' => [
                'scrapyard-io' => [
                    'dont-discover' => ['*'],
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($this->basePath.'/vendor/composer/installed.json', json_encode([
            [
                'name' => 'vendor/package',
                'extra' => ['scrapyard-io' => ['providers' => ['Package\\Provider']]],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame([], $this->manifest()->providers());
    }

    public function testAnExistingManifestCanBeLoadedWithoutInstalledMetadata(): void
    {
        file_put_contents(
            $this->basePath.'/bootstrap/cache/packages.php',
            "<?php return ['vendor/cached' => ['providers' => ['Cached\\\\Provider']]];",
        );

        $this->assertSame(['Cached\\Provider'], $this->manifest()->providers());
    }

    private function manifest(): PackageManifest
    {
        $manifest = new PackageManifest(
            new Filesystem(),
            $this->basePath,
            $this->basePath.'/bootstrap/cache/packages.php',
        );
        $manifest->vendorPath = $this->basePath.'/vendor';

        return $manifest;
    }
}
