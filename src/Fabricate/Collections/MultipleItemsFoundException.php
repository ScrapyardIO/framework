<?php

namespace Fabricate\NutsAndBolts;

use Throwable;
use RuntimeException;

class MultipleItemsFoundException extends RuntimeException
{
    /**
     * The number of items found.
     */
    public int $count;

    /**
     * Create a new exception instance.
     */
    public function __construct(int $count, int $code = 0, ?Throwable $previous = null)
    {
        $this->count = $count;

        parent::__construct("$count items were found.", $code, $previous);
    }

    /**
     * Get the number of items found.
     */
    public function getCount(): int
    {
        return $this->count;
    }
}
