<?php

namespace Fabricate\Contracts\Circuits\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class IntegratedCircuit
{
    /**
     * @var array<int, string>
     */
    public array $protocols;

    public function __construct(string ...$protocols)
    {
        $this->protocols = $protocols;
    }
}