<?php

namespace DeptOfScrapyardRobotics\Tests\Config;

use Fabricate\Config\Repository;
use Fabricate\NutsAndBolts\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    public function testItReadsAndWritesNestedValuesUsingDotNotation(): void
    {
        $repository = new Repository([
            'machine' => [
                'name' => 'Scrapyard',
            ],
        ]);

        $this->assertTrue($repository->has('machine.name'));
        $this->assertSame('Scrapyard', $repository->get('machine.name'));
        $this->assertSame('fallback', $repository->get('machine.missing', 'fallback'));

        $repository->set('machine.environment', 'testing');
        $repository->set([
            'logging.default' => 'stderr',
            'logging.enabled' => true,
        ]);

        $this->assertSame('testing', $repository->get('machine.environment'));
        $this->assertSame('stderr', $repository->get('logging.default'));
        $this->assertTrue($repository->get('logging.enabled'));
    }

    public function testItGetsManyValuesWithPerKeyDefaults(): void
    {
        $repository = new Repository(['present' => 'value']);

        $this->assertSame([
            'present' => 'value',
            'missing' => null,
            'other' => 'fallback',
        ], $repository->getMany([
            'present',
            'missing',
            'other' => 'fallback',
        ]));
    }

    public function testTypedAccessorsAndCollectionConversionReturnExpectedTypes(): void
    {
        $repository = new Repository([
            'string' => 'value',
            'integer' => 42,
            'float' => 4.2,
            'boolean' => true,
            'array' => ['first', 'second'],
        ]);

        $this->assertSame('value', $repository->string('string'));
        $this->assertSame(42, $repository->integer('integer'));
        $this->assertSame(4.2, $repository->float('float'));
        $this->assertTrue($repository->boolean('boolean'));
        $this->assertSame(['first', 'second'], $repository->array('array'));
        $this->assertInstanceOf(Collection::class, $repository->collection('array'));
        $this->assertSame(['first', 'second'], $repository->collection('array')->all());
    }

    #[DataProvider('invalidTypedValues')]
    public function testTypedAccessorsRejectValuesOfTheWrongType(
        string $method,
        mixed $value,
        string $expectedType,
    ): void {
        $repository = new Repository(['key' => $value]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Configuration value for key [key] must be {$expectedType}");

        $repository->{$method}('key');
    }

    public static function invalidTypedValues(): array
    {
        return [
            'string' => ['string', 1, 'a string'],
            'integer' => ['integer', '1', 'an integer'],
            'float' => ['float', 1, 'a float'],
            'boolean' => ['boolean', 1, 'a boolean'],
            'array' => ['array', 'value', 'an array'],
        ];
    }

    public function testArrayValuesCanBePrependedAndPushed(): void
    {
        $repository = new Repository(['providers' => ['middle']]);

        $repository->prepend('providers', 'first');
        $repository->push('providers', 'last');

        $this->assertSame(['first', 'middle', 'last'], $repository->get('providers'));
    }

    public function testArrayAccessMatchesRepositoryAccess(): void
    {
        $repository = new Repository();
        $repository['machine.name'] = 'Scrapyard';

        $this->assertTrue(isset($repository['machine.name']));
        $this->assertSame('Scrapyard', $repository['machine.name']);

        unset($repository['machine.name']);

        $this->assertNull($repository['machine.name']);
    }
}
