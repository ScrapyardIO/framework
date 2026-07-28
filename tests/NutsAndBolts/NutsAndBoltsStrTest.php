<?php

namespace DeptOfScrapyardRobotics\Tests\NutsAndBolts;

use Fabricate\NutsAndBolts\Str;
use Fabricate\NutsAndBolts\Stringable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\Ulid;

class NutsAndBoltsStrTest extends TestCase
{
    protected function tearDown(): void
    {
        Str::resetFactoryState();
        Str::flushMacros();

        parent::tearDown();
    }

    public function testItCreatesAFluentStringableValue(): void
    {
        $string = Str::of('Scrapyard IO');

        $this->assertInstanceOf(Stringable::class, $string);
        $this->assertSame('scrapyard-io', $string->slug()->toString());
    }

    public function testItExtractsRelativeStringSegments(): void
    {
        $this->assertSame('IO Framework', Str::after('Scrapyard IO Framework', 'Scrapyard '));
        $this->assertSame('Framework', Str::afterLast('Scrapyard IO Framework', ' '));
        $this->assertSame('Scrapyard', Str::before('Scrapyard IO Framework', ' '));
        $this->assertSame('Scrapyard IO', Str::beforeLast('Scrapyard IO Framework', ' '));
        $this->assertSame('IO', Str::between('Scrapyard [IO] Framework', '[', ']'));
        $this->assertSame('first', Str::betweenFirst('[first] [second]', '[', ']'));
    }

    public function testItConvertsCommonNamingCases(): void
    {
        $this->assertSame('scrapyardIoFramework', Str::camel('scrapyard io framework'));
        $this->assertSame('ScrapyardIoFramework', Str::studly('scrapyard_io-framework'));
        $this->assertSame('scrapyard_io_framework', Str::snake('scrapyard io framework'));
        $this->assertSame('scrapyard-io-framework', Str::kebab('scrapyard io framework'));
        $this->assertSame('Scrapyard Io Framework', Str::headline('scrapyard_ioFramework'));
    }

    public function testItChecksContentsPrefixesSuffixesAndPatterns(): void
    {
        $this->assertTrue(Str::contains('Scrapyard IO', ['missing', 'yard']));
        $this->assertTrue(Str::contains('Scrapyard IO', 'scrapyard', ignoreCase: true));
        $this->assertTrue(Str::containsAll('Scrapyard IO', ['Scrapyard', 'IO']));
        $this->assertTrue(Str::startsWith('Scrapyard IO', ['Framework', 'Scrap']));
        $this->assertTrue(Str::endsWith('Scrapyard IO', ['Framework', 'IO']));
        $this->assertTrue(Str::is('machine:*', 'machine:start'));
        $this->assertFalse(Str::doesntContain('Scrapyard IO', 'yard'));
    }

    public function testItValidatesStructuredIdentifiersAndValues(): void
    {
        $uuid = '01880dfa-2825-72e4-acbb-b1e4981cf8af';
        $ulid = '01DXH9C4P0ED4AGJJP9CRKQ55C';

        $this->assertTrue(Str::isJson('{"machine":"scrapyard"}'));
        $this->assertFalse(Str::isJson('{invalid'));
        $this->assertTrue(Str::isUrl('https://scrapyard.io'));
        $this->assertTrue(Str::isUuid($uuid));
        $this->assertTrue(Str::isUuid($uuid, 7));
        $this->assertTrue(Str::isUlid($ulid));
        $this->assertFalse(Str::isUuid($ulid));
    }

    public function testItTransformsAndReplacesStrings(): void
    {
        $this->assertSame('Scrapyard IO...', Str::limit('Scrapyard IO Framework', 12));
        $this->assertSame('one, two, three', Str::replaceArray('?', ['one', 'two', 'three'], '?, ?, ?'));
        $this->assertSame('Scrapyard Framework', Str::replaceFirst('IO', 'Framework', 'Scrapyard IO'));
        $this->assertSame('one two', Str::squish(" one \n two "));
        $this->assertSame('[Scrapyard]', Str::wrap('Scrapyard', '[', ']'));
        $this->assertSame('Scrapyard', Str::unwrap('[Scrapyard]', '[', ']'));
        $this->assertSame('U2NyYXB5YXJk', Str::toBase64('Scrapyard'));
        $this->assertSame('Scrapyard', Str::fromBase64('U2NyYXB5YXJk', strict: true));
    }

    public function testRandomStringFactoriesCanBeControlledAndReset(): void
    {
        Str::createRandomStringsUsing(fn (int $length) => str_repeat('x', $length));

        $this->assertSame('xxxxxxxx', Str::random(8));

        Str::createRandomStringsUsingSequence(['first', 'second']);

        $this->assertSame('first', Str::random());
        $this->assertSame('second', Str::random());

        Str::createRandomStringsNormally();

        $this->assertSame(12, strlen(Str::random(12)));
    }

    public function testItGeneratesUuidAndUlidObjects(): void
    {
        $this->assertInstanceOf(UuidInterface::class, Str::uuid());
        $this->assertInstanceOf(UuidInterface::class, Str::uuid7());
        $this->assertInstanceOf(Ulid::class, Str::ulid());
    }

    public function testItConvertsMarkdown(): void
    {
        $this->assertStringContainsString('<h1>Scrapyard</h1>', Str::markdown('# Scrapyard'));
        $this->assertStringContainsString('<strong>Scrapyard</strong>', Str::inlineMarkdown('**Scrapyard**'));
    }
}