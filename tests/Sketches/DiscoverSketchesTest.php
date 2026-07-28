<?php

namespace DeptOfScrapyardRobotics\Tests\Sketches;

use Fabricate\Config\Repository;
use Fabricate\Core\Machine;
use Fabricate\Sketches\DiscoverSketches;
use Fabricate\Sketches\SketchRegistry;
use Fabricate\Sketches\SketchesServiceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class DiscoverSketchesTest extends TestCase
{
    private string $basePath;

    /** @var callable(string): void|null */
    private $autoload = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/scrapyard-sketches-'.bin2hex(random_bytes(8));
        (new SymfonyFilesystem())->mkdir([
            $this->basePath.'/app/Sketches',
            $this->basePath.'/app/Other',
            $this->basePath.'/bootstrap/cache',
            $this->basePath.'/config',
            $this->basePath.'/storage',
        ]);
        $this->basePath = realpath($this->basePath) ?: $this->basePath;

        file_put_contents($this->basePath.'/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $basePath = $this->basePath;
        $this->autoload = static function (string $class) use ($basePath): void {
            $prefix = 'App\\';

            if (! str_starts_with($class, $prefix)) {
                return;
            }

            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $basePath.'/app/'.$relative.'.php';

            if (is_file($file)) {
                require_once $file;
            }
        };

        spl_autoload_register($this->autoload);
    }

    protected function tearDown(): void
    {
        if (! is_null($this->autoload)) {
            spl_autoload_unregister($this->autoload);
        }

        Machine::setInstance(null);
        (new SymfonyFilesystem())->remove($this->basePath);

        parent::tearDown();
    }

    public function testConventionDiscoveryRegistersKebabNamesAndSkipsAbstracts(): void
    {
        $this->writeAppSketchBase();
        $this->writePhpFile('app/Sketches/BlinkLed.php', <<<'PHP'
<?php

namespace App\Sketches;

use Fabricate\Contracts\Sketches\SketchLoopResult;

class BlinkLed extends Sketch
{
    public function loop(): SketchLoopResult
    {
        return SketchLoopResult::STOP;
    }
}
PHP);
        $this->writePhpFile('app/Sketches/AbstractHelper.php', <<<'PHP'
<?php

namespace App\Sketches;

abstract class AbstractHelper extends Sketch
{
}
PHP);
        $this->writePhpFile('app/Sketches/NotASketch.php', <<<'PHP'
<?php

namespace App\Sketches;

class NotASketch
{
}
PHP);

        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);

        $discovered = DiscoverSketches::within(
            $this->basePath.'/app/Sketches',
            $this->basePath,
            \App\Sketches\Sketch::class,
        );

        $this->assertSame(['blink-led' => 'App\\Sketches\\BlinkLed'], $discovered);
        $this->assertArrayNotHasKey('abstract-helper', $discovered);
        $this->assertArrayNotHasKey('not-a-sketch', $discovered);
        $this->assertArrayNotHasKey('sketch', $discovered);
    }

    public function testMissingSketchesDirectoryYieldsEmptyDiscovery(): void
    {
        $this->writeAppSketchBase();

        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);

        $discovered = DiscoverSketches::within(
            $this->basePath.'/app/MissingSketches',
            $this->basePath,
            \App\Sketches\Sketch::class,
        );

        $this->assertSame([], $discovered);
    }

    public function testProviderBootsConventionAndConfigLoadedSketches(): void
    {
        $this->writeAppSketchBase();
        $this->writePhpFile('app/Sketches/StatusLight.php', <<<'PHP'
<?php

namespace App\Sketches;

use Fabricate\Contracts\Sketches\SketchLoopResult;

class StatusLight extends Sketch
{
    public function loop(): SketchLoopResult
    {
        return SketchLoopResult::STOP;
    }
}
PHP);
        $this->writePhpFile('app/Other/ConfiguredSketch.php', <<<'PHP'
<?php

namespace App\Other;

use Fabricate\Contracts\Sketches\Attributes\Sketch;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch as BaseSketch;

#[Sketch('configured-blink')]
class ConfiguredSketch extends BaseSketch
{
    public function loop(): SketchLoopResult
    {
        return SketchLoopResult::STOP;
    }
}
PHP);

        $machine = new Machine($this->basePath);
        Machine::setInstance($machine);
        $machine->instance('config', new Repository([
            'sketches' => [
                'load' => [
                    \App\Other\ConfiguredSketch::class,
                ],
            ],
        ]));

        $machine->register(SketchesServiceProvider::class);
        $machine->boot();

        /** @var SketchRegistry $registry */
        $registry = $machine->make(SketchRegistry::class);

        $this->assertTrue($registry->has('status-light'));
        $this->assertTrue($registry->has('configured-blink'));
        $this->assertInstanceOf(\App\Sketches\StatusLight::class, $registry->resolve('status-light'));
        $this->assertInstanceOf(\App\Other\ConfiguredSketch::class, $registry->resolve('configured-blink'));
    }

    protected function writeAppSketchBase(): void
    {
        $this->writePhpFile('app/Sketches/Sketch.php', <<<'PHP'
<?php

namespace App\Sketches;

use Fabricate\Sketches\Sketch as FrameworkSketch;

abstract class Sketch extends FrameworkSketch
{
}
PHP);
    }

    protected function writePhpFile(string $relative, string $contents): void
    {
        $path = $this->basePath.'/'.$relative;
        (new SymfonyFilesystem())->mkdir(dirname($path));
        file_put_contents($path, $contents);
    }
}
