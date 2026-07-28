<?php

namespace DeptOfScrapyardRobotics\Tests\Sketches\Fixtures;

class Dependency
{
    public function __construct(public string $value = 'injected') {}
}
