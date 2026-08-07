<?php

namespace Fabricate\Contracts\JsonSchema;

use Closure;

interface JsonSchema
{
    /**
     * Create a new object schema instance.
     *
     * @param  (Closure(JsonSchema): array<string, \Fabricate\JsonSchema\Types\Type>)|array<string, \Fabricate\JsonSchema\Types\Type>  $properties
     * @return \Fabricate\JsonSchema\Types\ObjectType
     */
    public function object(Closure|array $properties = []);

    /**
     * Create a new array property instance.
     *
     * @return \Fabricate\JsonSchema\Types\ArrayType
     */
    public function array();

    /**
     * Create a new string property instance.
     *
     * @return \Fabricate\JsonSchema\Types\StringType
     */
    public function string();

    /**
     * Create a new integer property instance.
     *
     * @return \Fabricate\JsonSchema\Types\IntegerType
     */
    public function integer();

    /**
     * Create a new number property instance.
     *
     * @return \Fabricate\JsonSchema\Types\NumberType
     */
    public function number();

    /**
     * Create a new boolean property instance.
     *
     * @return \Fabricate\JsonSchema\Types\BooleanType
     */
    public function boolean();

    /**
     * Create a new multi-type union instance.
     *
     * @param  array<int, string>  $types
     * @return \Fabricate\JsonSchema\Types\UnionType
     */
    public function union(array $types);

    /**
     * Create a new anyOf schema instance.
     *
     * @param  (Closure(JsonSchema): array<int, \Fabricate\JsonSchema\Types\Type>)|array<int, \Fabricate\JsonSchema\Types\Type>  $schemas
     * @return \Fabricate\JsonSchema\Types\AnyOfType
     */
    public function anyOf(Closure|array $schemas);
}
