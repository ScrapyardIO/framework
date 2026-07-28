<?php

namespace Fabricate\Queue\Attributes;

use Attribute;
use UnitEnum;

use function Fabricate\NutsAndBolts\Helpers\enum_value;

#[Attribute(Attribute::TARGET_CLASS)]
class Queue
{
    /**
     * Create a new attribute instance.
     *
     * @param  UnitEnum|string  $queue
     */
    public function __construct(public UnitEnum|string $queue)
    {
        $this->queue = enum_value($queue);
    }
}
