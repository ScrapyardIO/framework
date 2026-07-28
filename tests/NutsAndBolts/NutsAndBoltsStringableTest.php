<?php

namespace DeptOfScrapyardRobotics\Tests\NutsAndBolts;

use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Stringable;
use PHPUnit\Framework\TestCase;

class NutsAndBoltsStringableTest extends TestCase
{
    protected function tearDown(): void
    {
        Stringable::flushMacros();

        parent::tearDown();
    }

    public function testItWrapsAStringAndKeepsTransformationsImmutable(): void
    {
        $original = new Stringable(' Scrapyard IO ');
        $transformed = $original->trim()->lower()->replace('io', 'framework');

        $this->assertSame(' Scrapyard IO ', $original->toString());
        $this->assertSame('scrapyard framework', $transformed->toString());
        $this->assertSame('Scrapyard IO', (string) $original->trim());
    }

    public function testFluentStringOperationsDelegateToStr(): void
    {
        $string = new Stringable('Scrapyard IO Framework');

        $this->assertSame('IO Framework', $string->after('Scrapyard ')->value());
        $this->assertSame('Scrapyard IO', $string->beforeLast(' Framework')->value());
        $this->assertTrue($string->contains(['missing', 'IO']));
        $this->assertTrue($string->startsWith('Scrapyard'));
        $this->assertTrue($string->endsWith('Framework'));
        $this->assertSame('scrapyard-io-framework', $string->lower()->slug()->value());
        $this->assertSame('krowemarF OI drayparcS', $string->reverse()->value());
        $this->assertSame('Scrapyard IO...', $string->limit(12)->value());
    }

    public function testItSplitsIntoFrameworkCollections(): void
    {
        $exploded = (new Stringable('first,second,third'))->explode(',');
        $split = (new Stringable('abcdef'))->split(2);

        $this->assertInstanceOf(Collection::class, $exploded);
        $this->assertSame(['first', 'second', 'third'], $exploded->all());
        $this->assertInstanceOf(Collection::class, $split);
        $this->assertSame(['ab', 'cd', 'ef'], $split->all());
    }

    public function testConditionalOperationsRunTheMatchingBranch(): void
    {
        $string = new Stringable('Scrapyard IO');

        $matching = $string->whenContains(
            'IO',
            fn (Stringable $value) => $value->append(' Framework'),
            fn (Stringable $value) => $value->append(' Missing'),
        );
        $default = $string->whenEmpty(
            fn (Stringable $value) => $value->append(' Empty'),
            fn (Stringable $value) => $value->append(' Present'),
        );

        $this->assertSame('Scrapyard IO Framework', $matching->value());
        $this->assertSame('Scrapyard IO Present', $default->value());
    }

    public function testItConvertsScalarValues(): void
    {
        $this->assertSame(255, (new Stringable('ff'))->toInteger(16));
        $this->assertSame(42.5, (new Stringable('42.5'))->toFloat());
        $this->assertTrue((new Stringable('yes'))->toBoolean());
        $this->assertFalse((new Stringable('off'))->toBoolean());
    }

    public function testItSupportsArrayAccessAndJsonSerialization(): void
    {
        $string = new Stringable('yard');

        $this->assertTrue(isset($string[1]));
        $this->assertSame('a', $string[1]);

        $string[0] = 'Y';
        unset($string[3]);

        $this->assertSame('Yar', $string->value());
        $this->assertSame('"Yar"', json_encode($string, JSON_THROW_ON_ERROR));
    }

    public function testItSupportsBase64AndHashTransformations(): void
    {
        $encoded = (new Stringable('Scrapyard'))->toBase64();

        $this->assertSame('U2NyYXB5YXJk', $encoded->value());
        $this->assertSame('Scrapyard', $encoded->fromBase64(strict: true)->value());
        $this->assertSame(hash('sha256', 'Scrapyard'), (new Stringable('Scrapyard'))->hash('sha256')->value());
    }

    public function testMacrosCanExtendTheFluentApi(): void
    {
        Stringable::macro('frameworkName', function (): Stringable {
            return $this->append(' Framework');
        });

        $this->assertSame('ScrapyardIO Framework', (new Stringable('ScrapyardIO'))->frameworkName()->value());
    }
}