<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\Reflection;

class AttributedMethod
{
    public function withAttribute(#[ReflectableAttribute('marked-parameter')] string $marked, string $unmarked): void
    {
    }

    public function withoutAttribute(string $plain): void
    {
    }
}
