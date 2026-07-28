<?php

namespace DeptOfScrapyardRobotics\Tests\Conditionable;

use Fabricate\NutsAndBolts\Concerns\Conditionable;
use PHPUnit\Framework\TestCase;

class ConditionableTest extends TestCase
{
    public function testWhenConditionCallback(): void
    {
        $logger = (new ConditionableLogger())
            ->when(
                2,
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('when', $condition),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('default', $condition),
            );

        $this->assertSame(['when', 2], $logger->values);

        $logger = (new ConditionableLogger())->log('init')
            ->when(
                fn (ConditionableLogger $logger) => $logger->has('init'),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('when', $condition),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('default', $condition),
            );

        $this->assertSame(['init', 'when', true], $logger->values);
    }

    public function testWhenDefaultCallback(): void
    {
        $logger = (new ConditionableLogger())
            ->when(
                null,
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('when', $condition),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('default', $condition),
            );

        $this->assertSame(['default', null], $logger->values);

        $logger = (new ConditionableLogger())
            ->when(
                fn (ConditionableLogger $logger) => $logger->has('missing'),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('when', $condition),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('default', $condition),
            );

        $this->assertSame(['default', false], $logger->values);
    }

    public function testUnlessConditionCallback(): void
    {
        $logger = (new ConditionableLogger())
            ->unless(
                null,
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('unless', $condition),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('default', $condition),
            );

        $this->assertSame(['unless', null], $logger->values);

        $logger = (new ConditionableLogger())
            ->unless(
                fn (ConditionableLogger $logger) => $logger->has('missing'),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('unless', $condition),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('default', $condition),
            );

        $this->assertSame(['unless', false], $logger->values);
    }

    public function testUnlessDefaultCallback(): void
    {
        $logger = (new ConditionableLogger())
            ->unless(
                2,
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('unless', $condition),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('default', $condition),
            );

        $this->assertSame(['default', 2], $logger->values);

        $logger = (new ConditionableLogger())->log('init')
            ->unless(
                fn (ConditionableLogger $logger) => $logger->has('init'),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('unless', $condition),
                fn (ConditionableLogger $logger, mixed $condition) => $logger->log('default', $condition),
            );

        $this->assertSame(['init', 'default', true], $logger->values);
    }

    public function testWhenProxy(): void
    {
        $logger = (new ConditionableLogger())
            ->when(true)->log('one')
            ->when(false)->log('two');

        $this->assertSame(['one'], $logger->values);

        $logger = (new ConditionableLogger())->log('init')
            ->when(fn (ConditionableLogger $logger) => $logger->has('init'))
            ->log('one')
            ->when(fn (ConditionableLogger $logger) => $logger->has('missing'))
            ->log('two')
            ->when()->has('init')->log('three')
            ->when()->has('missing')->log('four')
            ->when()->toggle->log('five')
            ->toggle()
            ->when()->toggle->log('six');

        $this->assertSame(['init', 'one', 'three', 'six'], $logger->values);
    }

    public function testUnlessProxy(): void
    {
        $logger = (new ConditionableLogger())
            ->unless(true)->log('one')
            ->unless(false)->log('two');

        $this->assertSame(['two'], $logger->values);

        $logger = (new ConditionableLogger())->log('init')
            ->unless(fn (ConditionableLogger $logger) => $logger->has('init'))
            ->log('one')
            ->unless(fn (ConditionableLogger $logger) => $logger->has('missing'))
            ->log('two')
            ->unless()->has('init')->log('three')
            ->unless()->has('missing')->log('four')
            ->unless()->toggle->log('five')
            ->toggle()
            ->unless()->toggle->log('six');

        $this->assertSame(['init', 'two', 'four', 'five'], $logger->values);
    }
}

class ConditionableLogger
{
    use Conditionable;

    public array $values = [];

    public bool $toggle = false;

    public function log(mixed ...$values): static
    {
        array_push($this->values, ...$values);

        return $this;
    }

    public function has(mixed $value): bool
    {
        return in_array($value, $this->values, true);
    }

    public function toggle(): static
    {
        $this->toggle = ! $this->toggle;

        return $this;
    }
}