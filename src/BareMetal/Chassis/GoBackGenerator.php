<?php

namespace BareMetal\Chassis;

use Closure;
use Countable;
use Traversable;
use IteratorAggregate;

class GoBackGenerator implements Countable, IteratorAggregate
{
    /**
     * Create a new generator instance.
     */
    public function __construct(
        protected Closure $generator,
        protected Closure|int $count) {}

    /**
     * Get an iterator from the generator.
     */
    public function getIterator(): Traversable
    {
        return ($this->generator)();
    }

    /**
     * Get the total number of tagged services.
     */
    public function count(): int
    {
        if (is_callable($count = $this->count)) {
            $this->count = $count();
        }

        return $this->count;
    }
}
