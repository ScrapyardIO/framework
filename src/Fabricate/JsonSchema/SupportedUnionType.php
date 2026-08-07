<?php

namespace Fabricate\JsonSchema;

enum SupportedUnionType: string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case NUMBER = 'number';
    case BOOLEAN = 'boolean';
    case OBJECT = 'object';
    case ARRAY = 'array';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
