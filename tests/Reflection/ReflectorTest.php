<?php

namespace DeptOfScrapyardRobotics\Tests\Reflection;

use Attribute;
use Closure;
use Fabricate\NutsAndBolts\Concerns\ReflectsClosures;
use Fabricate\NutsAndBolts\Reflector;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionParameter;
use RuntimeException;

class ReflectorTest extends TestCase
{
    public function testItDeterminesWhetherValuesAreCallable(): void
    {
        $subject = new CallableSubject();

        $this->assertTrue(Reflector::isCallable(fn (): null => null));
        $this->assertTrue(Reflector::isCallable([$subject, 'publicMethod']));
        $this->assertFalse(Reflector::isCallable([$subject, 'protectedMethod']));
        $this->assertTrue(Reflector::isCallable([$subject, 'dynamicMethod']));
        $this->assertTrue(Reflector::isCallable([CallableSubject::class, 'dynamicStaticMethod']));
        $this->assertFalse(Reflector::isCallable(['MissingClass', 'method']));
    }

    public function testItReadsAttributesFromAClassAndItsParents(): void
    {
        $direct = Reflector::getClassAttributes(AttributedChild::class, Marker::class);
        $inherited = Reflector::getClassAttributes(AttributedChild::class, Marker::class, includeParents: true);

        $this->assertSame(['child'], $direct->map(fn (Marker $marker) => $marker->name)->all());
        $this->assertSame(
            ['child', 'parent'],
            $inherited->flatten()->map(fn (Marker $marker) => $marker->name)->all(),
        );
        $this->assertSame('child', Reflector::getClassAttribute(AttributedChild::class, Marker::class)->name);
    }

    public function testItResolvesParameterClassNamesIncludingSelfParentAndUnions(): void
    {
        $this->assertSame(
            ReflectionDependency::class,
            Reflector::getParameterClassName($this->parameter(ReflectionChild::class, 'dependency')),
        );
        $this->assertSame(
            ReflectionChild::class,
            Reflector::getParameterClassName($this->parameter(ReflectionChild::class, 'selfType')),
        );
        $this->assertSame(
            ReflectionParent::class,
            Reflector::getParameterClassName($this->parameter(ReflectionChild::class, 'parentType')),
        );
        $this->assertSame(
            [ReflectionDependency::class, OtherDependency::class],
            Reflector::getParameterClassNames($this->parameter(ReflectionChild::class, 'unionType')),
        );
    }

    public function testItIdentifiesSubclassAndStringBackedEnumParameters(): void
    {
        $this->assertTrue(Reflector::isParameterSubclassOf(
            $this->parameter(ReflectionChild::class, 'parentType'),
            ReflectionDependency::class,
        ));
        $this->assertTrue(Reflector::isParameterBackedEnumWithStringBackingType(
            $this->parameter(ReflectionChild::class, 'state'),
        ));
        $this->assertFalse(Reflector::isParameterBackedEnumWithStringBackingType(
            $this->parameter(ReflectionChild::class, 'dependency'),
        ));
    }

    public function testClosureReflectionReturnsParameterAndReturnTypes(): void
    {
        $harness = new ClosureReflectionHarness();
        $closure = fn (ReflectionDependency|OtherDependency $dependency): AttributedChild => new AttributedChild();

        $this->assertSame(
            [ReflectionDependency::class, OtherDependency::class],
            $harness->firstParameterTypes($closure),
        );
        $this->assertSame([AttributedChild::class], $harness->returnTypes($closure));
    }

    public function testClosureReflectionRejectsClosuresWithoutParameters(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The given Closure has no parameters.');

        (new ClosureReflectionHarness())->firstParameterTypes(fn (): null => null);
    }

    private function parameter(string $class, string $method): ReflectionParameter
    {
        return (new ReflectionMethod($class, $method))->getParameters()[0];
    }
}

#[Attribute(Attribute::TARGET_CLASS)]
class Marker
{
    public function __construct(public string $name)
    {
    }
}

#[Marker('parent')]
class AttributedParent
{
}

#[Marker('child')]
class AttributedChild extends AttributedParent
{
}

class CallableSubject
{
    public function publicMethod(): void
    {
    }

    protected function protectedMethod(): void
    {
    }

    public function __call(string $method, array $parameters): mixed
    {
        return null;
    }

    public static function __callStatic(string $method, array $parameters): mixed
    {
        return null;
    }
}

class ReflectionDependency
{
}

class ReflectionParent extends ReflectionDependency
{
}

class OtherDependency
{
}

class ReflectionChild extends ReflectionParent
{
    public function dependency(ReflectionDependency $dependency): void
    {
    }

    public function selfType(self $dependency): void
    {
    }

    public function parentType(parent $dependency): void
    {
    }

    public function unionType(ReflectionDependency|OtherDependency|string $dependency): void
    {
    }

    public function state(MACHINE_STATE $state): void
    {
    }
}

enum MACHINE_STATE: string
{
    case READY = 'ready';
}

class ClosureReflectionHarness
{
    use ReflectsClosures;

    public function firstParameterTypes(Closure $closure): array
    {
        return $this->firstClosureParameterTypes($closure);
    }

    public function returnTypes(Closure $closure): array
    {
        return $this->closureReturnTypes($closure);
    }
}
