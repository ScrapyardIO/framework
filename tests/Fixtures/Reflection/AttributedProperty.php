<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\Reflection;

class AttributedProperty
{
    #[ReflectableAttribute('marked-property')]
    public string $marked = 'yes';

    public string $unmarked = 'no';
}
