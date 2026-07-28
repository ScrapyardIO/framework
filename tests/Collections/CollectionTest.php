<?php

namespace DeptOfScrapyardRobotics\Tests\Collections;

use Fabricate\Contracts\NutsAndBolts\Arrayable;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Exceptions\ItemNotFoundException;
use Fabricate\NutsAndBolts\Exceptions\MultipleItemsFoundException;
use Fabricate\NutsAndBolts\LazyCollection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testItMapsFiltersAndReducesValues(): void
    {
        $collection = new Collection([1, 2, 3, 4]);

        $result = $collection
            ->filter(fn (int $value) => $value % 2 === 0)
            ->map(fn (int $value) => $value * 10);

        $this->assertSame([1 => 20, 3 => 40], $result->all());
        $this->assertSame(10, $collection->sum());
        $this->assertSame(2.5, $collection->avg());
        $this->assertSame(24, $collection->reduce(fn (int $carry, int $value) => $carry * $value, 1));
    }

    public function testItGroupsKeysAndPlucksStructuredValues(): void
    {
        $machines = new Collection([
            ['id' => 'arm', 'type' => 'actuator', 'meta' => ['name' => 'Arm']],
            ['id' => 'rover', 'type' => 'mobile', 'meta' => ['name' => 'Rover']],
            ['id' => 'gripper', 'type' => 'actuator', 'meta' => ['name' => 'Gripper']],
        ]);

        $this->assertSame([
            'arm' => 'Arm',
            'rover' => 'Rover',
            'gripper' => 'Gripper',
        ], $machines->pluck('meta.name', 'id')->all());
        $this->assertSame(['actuator', 'mobile'], $machines->groupBy('type')->keys()->all());
        $this->assertSame('Rover', $machines->keyBy('id')->get('rover')['meta']['name']);
    }

    public function testItSupportsMutableCollectionOperations(): void
    {
        $collection = new Collection(['second']);
        $collection->prepend('first');
        $collection->push('third');
        $collection->put('named', 'value');

        $this->assertSame(['first', 'second', 'third', 'named' => 'value'], $collection->all());
        $this->assertSame('first', $collection->shift());
        $this->assertSame('value', $collection->pull('named'));
        $this->assertSame('third', $collection->pop());
        $this->assertSame(['second'], $collection->values()->all());
    }

    public function testSoleReturnsOnlyOneMatchingItem(): void
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertSame(2, $collection->sole(fn (int $value) => $value === 2));
    }

    public function testSoleRejectsNoMatchingItems(): void
    {
        $this->expectException(ItemNotFoundException::class);

        (new Collection([1, 2]))->sole(fn (int $value) => $value === 3);
    }

    public function testSoleRejectsMultipleMatchingItems(): void
    {
        $this->expectException(MultipleItemsFoundException::class);

        (new Collection([1, 2, 3, 4]))->sole(fn (int $value) => $value % 2 === 0);
    }

    public function testConditionableOperationsPreserveFluentChaining(): void
    {
        $collection = new Collection([1, 2]);

        $result = $collection
            ->when(true, fn (Collection $values) => $values->push(3))
            ->unless(false, fn (Collection $values) => $values->push(4));

        $this->assertSame([1, 2, 3, 4], $result->all());
    }

    public function testHigherOrderProxiesMapPropertiesAndMethods(): void
    {
        $machines = new Collection([
            new MachineRecord('Arm'),
            new MachineRecord('Rover'),
        ]);

        $this->assertSame(['Arm', 'Rover'], $machines->map->name->all());
        $this->assertSame(['ARM', 'ROVER'], $machines->map->label()->all());
    }

    public function testLazyCollectionsDeferGeneratorExecution(): void
    {
        $iterations = 0;
        $lazy = new LazyCollection(function () use (&$iterations): iterable {
            foreach ([1, 2, 3] as $value) {
                $iterations++;
                yield $value;
            }
        });
        $mapped = $lazy->map(fn (int $value) => $value * 2);

        $this->assertSame(0, $iterations);
        $this->assertSame([2, 4], $mapped->take(2)->values()->all());
        $this->assertSame(2, $iterations);
    }

    public function testItSerializesToArraysAndJson(): void
    {
        $collection = new Collection([
            'machine' => new MachineRecord('Arm'),
        ]);

        $this->assertSame(['machine' => ['name' => 'Arm']], $collection->toArray());
        $this->assertSame('{"machine":{"name":"Arm"}}', $collection->toJson());
    }
}

class MachineRecord implements Arrayable
{
    public function __construct(public string $name)
    {
    }

    public function label(): string
    {
        return strtoupper($this->name);
    }

    public function toArray(): array
    {
        return ['name' => $this->name];
    }
}
