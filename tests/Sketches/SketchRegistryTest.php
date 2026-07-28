<?php

namespace DeptOfScrapyardRobotics\Tests\Sketches;

use DeptOfScrapyardRobotics\Tests\Sketches\Fixtures\AttributedPackageSketch;
use DeptOfScrapyardRobotics\Tests\Sketches\Fixtures\Dependency;
use DeptOfScrapyardRobotics\Tests\Sketches\Fixtures\InjectedSketch;
use DeptOfScrapyardRobotics\Tests\Sketches\Fixtures\MissingAttributeSketch;
use Fabricate\Chassis\Chassis;
use Fabricate\Contracts\Sketches\SketchException;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\SketchRegistry;
use Fabricate\Sketches\SketchRunner;
use PHPUnit\Framework\TestCase;

class SketchRegistryTest extends TestCase
{
    public function testAttributedPackageRegistrationAndResolution(): void
    {
        $container = new Chassis;
        $registry = new SketchRegistry($container);

        $registry->register(AttributedPackageSketch::class);

        $this->assertTrue($registry->has('package-blink'));
        $this->assertTrue($registry->has('PACKAGE-BLINK'));
        $this->assertInstanceOf(AttributedPackageSketch::class, $registry->resolve('package-blink'));
    }

    public function testPackageRegistrationRejectsMissingAttribute(): void
    {
        $registry = new SketchRegistry(new Chassis);

        $this->expectException(SketchException::class);
        $this->expectExceptionMessage('must declare the #[');

        $registry->register(MissingAttributeSketch::class);
    }

    public function testDuplicateNamesAreRejected(): void
    {
        $registry = new SketchRegistry(new Chassis);
        $registry->register(AttributedPackageSketch::class);

        $this->expectException(SketchException::class);
        $this->expectExceptionMessage('already registered');

        $registry->registerConvention('package-blink', MissingAttributeSketch::class);
    }

    public function testUnknownNamesThrow(): void
    {
        $registry = new SketchRegistry(new Chassis);

        $this->expectException(SketchException::class);
        $this->expectExceptionMessage('is not registered');

        $registry->resolve('missing-sketch');
    }

    public function testConstructorDependenciesAreInjectedAndLifecycleMethodsAreNot(): void
    {
        $container = new Chassis;
        $container->instance(Dependency::class, new Dependency('from-container'));
        $container->singleton(SketchRunner::class, fn () => new SketchRunner);

        $registry = new SketchRegistry($container);
        $registry->register(InjectedSketch::class);

        $sketch = $registry->resolve('injected-sketch');
        $this->assertInstanceOf(InjectedSketch::class, $sketch);
        $this->assertSame('from-container', $sketch->dependency->value);

        $runner = $container->make(SketchRunner::class);
        $runner->run($sketch);

        $this->assertInstanceOf(Dependency::class, $sketch->lifecycleDependency);
        $this->assertSame('lifecycle', $sketch->lifecycleDependency->value);
        $this->assertSame(SketchLoopResult::STOP, SketchLoopResult::STOP);
    }
}
