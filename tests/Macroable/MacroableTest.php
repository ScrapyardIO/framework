<?php

namespace DeptOfScrapyardRobotics\Tests\Macroable;

use BadMethodCallException;
use Fabricate\NutsAndBolts\Concerns\Macroable;
use PHPUnit\Framework\TestCase;

class MacroableTest extends TestCase
{
    protected function tearDown(): void
    {
        MacroableSubject::flushMacros();

        parent::tearDown();
    }

    public function testMacrosCanBeRegisteredAndCalledStaticallyOrOnAnInstance(): void
    {
        MacroableSubject::macro('greet', fn (string $name = 'Guest') => "Hello, {$name}");

        $this->assertTrue(MacroableSubject::hasMacro('greet'));
        $this->assertFalse(MacroableSubject::hasMacro('missing'));
        $this->assertSame('Hello, Guest', MacroableSubject::greet());
        $this->assertSame('Hello, Scrapyard', (new MacroableSubject())->greet('Scrapyard'));
    }

    public function testInstanceAndStaticClosuresAreBoundToTheExpectedScope(): void
    {
        MacroableSubject::macro('instanceValue', function (): string {
            return $this->instanceValue;
        });
        MacroableSubject::macro('staticValue', function (): string {
            return static::readStaticValue();
        });

        $this->assertSame('instance', (new MacroableSubject())->instanceValue());
        $this->assertSame('static', MacroableSubject::staticValue());
    }

    public function testStaticClosuresCanBeCalledOnAnInstance(): void
    {
        MacroableSubject::macro('staticClosure', static fn (): string => 'static closure');

        $this->assertSame('static closure', (new MacroableSubject())->staticClosure());
    }

    public function testMixinsRegisterPublicAndProtectedMethods(): void
    {
        MacroableSubject::mixin(new MacroableMixin());

        $this->assertSame('instance-Scrapyard', (new MacroableSubject())->fromMixin('Scrapyard'));
    }

    public function testMixinsCanPreserveOrReplaceExistingMacros(): void
    {
        MacroableSubject::macro('replaceable', fn (): string => 'original');
        MacroableSubject::mixin(new MacroableMixin(), false);

        $this->assertSame('original', MacroableSubject::replaceable());

        MacroableSubject::mixin(new MacroableMixin());

        $this->assertSame('replacement', MacroableSubject::replaceable());
    }

    public function testMacrosCanBeFlushed(): void
    {
        MacroableSubject::macro('temporary', fn (): string => 'registered');

        $this->assertSame('registered', MacroableSubject::temporary());

        MacroableSubject::flushMacros();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method '.MacroableSubject::class.'::temporary does not exist.');

        MacroableSubject::temporary();
    }

    public function testCallingAnUndefinedInstanceMacroThrowsAnException(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method '.MacroableSubject::class.'::missing does not exist.');

        (new MacroableSubject())->missing();
    }
}

class MacroableSubject
{
    use Macroable;

    protected string $instanceValue = 'instance';

    protected static function readStaticValue(): string
    {
        return 'static';
    }
}

class MacroableMixin
{
    public function fromMixin(): callable
    {
        return fn (string $value): string => $this->instanceValue.'-'.$value;
    }

    protected function replaceable(): callable
    {
        return fn (): string => 'replacement';
    }
}
