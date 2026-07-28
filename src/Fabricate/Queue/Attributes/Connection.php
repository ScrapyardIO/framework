<?php

namespace Fabricate\Queue\Attributes;

use Attribute;
use UnitEnum;

use function Fabricate\NutsAndBolts\Helpers\enum_value;

#[Attribute(Attribute::TARGET_CLASS)]
class Connection
{
    /**
     * Create a new attribute instance.
     *
     * @param  UnitEnum|string  $connection
     */
    public function __construct(public UnitEnum|string $connection)
    {
        $this->connection = enum_value($connection);
    }
}
