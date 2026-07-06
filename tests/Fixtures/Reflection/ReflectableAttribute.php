<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\Reflection;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::TARGET_PARAMETER)]
class ReflectableAttribute
{
    public function __construct(public string $value = 'default')
    {
    }
}
