<?php

namespace DeptOfScrapyardRobotics\Tests\Collections;

use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Exceptions\ItemNotFoundException;
use Fabricate\NutsAndBolts\Exceptions\MultipleItemsFoundException;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    public function testDotNotationCanReadWriteAndForgetNestedValues(): void
    {
        $array = ['machine' => ['name' => 'Scrapyard']];

        $this->assertSame('Scrapyard', Arr::get($array, 'machine.name'));
        $this->assertTrue(Arr::has($array, ['machine.name']));
        $this->assertFalse(Arr::hasAny($array, ['missing', 'other']));

        Arr::set($array, 'machine.environment', 'testing');
        Arr::push($array, 'machine.providers', 'Events', 'Console');
        Arr::forget($array, 'machine.name');

        $this->assertSame('testing', Arr::get($array, 'machine.environment'));
        $this->assertSame(['Events', 'Console'], Arr::get($array, 'machine.providers'));
        $this->assertFalse(Arr::has($array, 'machine.name'));
    }

    public function testArraysCanBeFlattenedToDotNotationAndRestored(): void
    {
        $nested = [
            'machine' => [
                'name' => 'Scrapyard',
                'features' => [
                    'events' => true,
                ],
            ],
        ];

        $dotted = Arr::dot($nested);

        $this->assertSame([
            'machine.name' => 'Scrapyard',
            'machine.features.events' => true,
        ], $dotted);
        $this->assertSame($nested, Arr::undot($dotted));
    }

    public function testItPlucksAndKeysNestedRecords(): void
    {
        $machines = [
            ['id' => 'alpha', 'meta' => ['name' => 'Arm']],
            ['id' => 'beta', 'meta' => ['name' => 'Rover']],
        ];

        $this->assertSame(['alpha' => 'Arm', 'beta' => 'Rover'], Arr::pluck($machines, 'meta.name', 'id'));
        $this->assertSame([
            'alpha' => $machines[0],
            'beta' => $machines[1],
        ], Arr::keyBy($machines, 'id'));
    }

    public function testFirstLastAndSoleSelectExpectedValues(): void
    {
        $values = [1, 2, 3, 4];

        $this->assertSame(1, Arr::first($values));
        $this->assertSame(4, Arr::last($values));
        $this->assertSame(2, Arr::first($values, fn (int $value) => $value % 2 === 0));
        $this->assertSame(4, Arr::last($values, fn (int $value) => $value % 2 === 0));
        $this->assertSame(3, Arr::sole($values, fn (int $value) => $value === 3));
    }

    public function testSoleRejectsNoMatches(): void
    {
        $this->expectException(ItemNotFoundException::class);

        Arr::sole([1, 2], fn (int $value) => $value === 3);
    }

    public function testSoleRejectsMultipleMatches(): void
    {
        $this->expectException(MultipleItemsFoundException::class);

        Arr::sole([1, 2, 3, 4], fn (int $value) => $value % 2 === 0);
    }

    public function testCommonSelectionAndTransformationHelpersPreserveKeys(): void
    {
        $values = ['first' => 1, 'second' => 2, 'third' => 3];

        $this->assertSame(['first' => 1, 'third' => 3], Arr::except($values, 'second'));
        $this->assertSame(['second' => 2], Arr::only($values, 'second'));
        $this->assertSame(['first' => 2, 'second' => 4, 'third' => 6], Arr::map(
            $values,
            fn (int $value) => $value * 2,
        ));
        $this->assertSame(['second' => 2, 'third' => 3], Arr::where(
            $values,
            fn (int $value) => $value > 1,
        ));
        $this->assertSame('one, two and three', Arr::join(['one', 'two', 'three'], ', ', ' and '));
    }
}
