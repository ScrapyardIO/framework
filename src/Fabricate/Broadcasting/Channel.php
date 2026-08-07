<?php

namespace Fabricate\Broadcasting;

use Stringable;

class Channel implements Stringable
{
    public function __construct(public string $name) {}
    public function __toString(): string { return $this->name; }
}
