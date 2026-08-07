<?php

namespace Fabricate\Validation;

use Fabricate\NutsAndBolts\Contracts\Arrayable;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Concerns\Macroable;
use Fabricate\Validation\Rules\AnyOf;
use Fabricate\Validation\Rules\ArrayKeys;
use Fabricate\Validation\Rules\ArrayRule;
use Fabricate\Validation\Rules\Can;
use Fabricate\Validation\Rules\Date;
use Fabricate\Validation\Rules\Dimensions;
use Fabricate\Validation\Rules\Email;
use Fabricate\Validation\Rules\Enum;
use Fabricate\Validation\Rules\ExcludeIf;
use Fabricate\Validation\Rules\ExcludeUnless;
use Fabricate\Validation\Rules\Exists;
use Fabricate\Validation\Rules\File;
use Fabricate\Validation\Rules\ImageFile;
use Fabricate\Validation\Rules\In;
use Fabricate\Validation\Rules\NotIn;
use Fabricate\Validation\Rules\Numeric;
use Fabricate\Validation\Rules\ProhibitedIf;
use Fabricate\Validation\Rules\ProhibitedUnless;
use Fabricate\Validation\Rules\RequiredIf;
use Fabricate\Validation\Rules\RequiredUnless;
use Fabricate\Validation\Rules\StringRule;
use Fabricate\Validation\Rules\Unique;

class Rule
{
    use Macroable;

    /**
     * Get a can constraint builder instance.
     *
     * @param  string  $ability
     * @param  mixed  ...$arguments
     * @return \Fabricate\Validation\Rules\Can
     */
    public static function can($ability, ...$arguments)
    {
        return new Can($ability, $arguments);
    }

    /**
     * Apply the given rules if the given condition is truthy.
     *
     * @param  callable|bool  $condition
     * @param  \Fabricate\Contracts\Validation\ValidationRule|\Fabricate\Contracts\Validation\InvokableRule|\Fabricate\Contracts\Validation\Rule|\Closure|array|string  $rules
     * @param  \Fabricate\Contracts\Validation\ValidationRule|\Fabricate\Contracts\Validation\InvokableRule|\Fabricate\Contracts\Validation\Rule|\Closure|array|string  $defaultRules
     * @return \Fabricate\Validation\ConditionalRules
     */
    public static function when($condition, $rules, $defaultRules = [])
    {
        return new ConditionalRules($condition, $rules, $defaultRules);
    }

    /**
     * Apply the given rules if the given condition is falsy.
     *
     * @param  callable|bool  $condition
     * @param  \Fabricate\Contracts\Validation\ValidationRule|\Fabricate\Contracts\Validation\InvokableRule|\Fabricate\Contracts\Validation\Rule|\Closure|array|string  $rules
     * @param  \Fabricate\Contracts\Validation\ValidationRule|\Fabricate\Contracts\Validation\InvokableRule|\Fabricate\Contracts\Validation\Rule|\Closure|array|string  $defaultRules
     * @return \Fabricate\Validation\ConditionalRules
     */
    public static function unless($condition, $rules, $defaultRules = [])
    {
        return new ConditionalRules($condition, $defaultRules, $rules);
    }

    /**
     * Get an array rule builder instance.
     *
     * @param  array|null  $keys
     * @return \Fabricate\Validation\Rules\ArrayRule
     */
    public static function array($keys = null)
    {
        return new ArrayRule(...func_get_args());
    }

    /**
     * Get an array keys rule builder instance.
     *
     * @param  \Fabricate\NutsAndBolts\Contracts\Arrayable|array|string  $keys
     * @return \Fabricate\Validation\Rules\ArrayKeys
     */
    public static function arrayKeys($keys)
    {
        return new ArrayKeys(...func_get_args());
    }

    /**
     * Create a new nested rule set.
     *
     * @param  callable  $callback
     * @return \Fabricate\Validation\NestedRules
     */
    public static function forEach($callback)
    {
        return new NestedRules($callback);
    }

    /**
     * Get a unique constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Fabricate\Validation\Rules\Unique
     */
    public static function unique($table, $column = 'NULL')
    {
        return new Unique($table, $column);
    }

    /**
     * Get an exists constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Fabricate\Validation\Rules\Exists
     */
    public static function exists($table, $column = 'NULL')
    {
        return new Exists($table, $column);
    }

    /**
     * Get an in rule builder instance.
     *
     * @param  \Fabricate\NutsAndBolts\Contracts\Arrayable|\UnitEnum|array|string  $values
     * @return \Fabricate\Validation\Rules\In
     */
    public static function in($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new In(is_array($values) ? $values : func_get_args());
    }

    /**
     * Get a not_in rule builder instance.
     *
     * @param  \Fabricate\NutsAndBolts\Contracts\Arrayable|\UnitEnum|array|string  $values
     * @return \Fabricate\Validation\Rules\NotIn
     */
    public static function notIn($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new NotIn(is_array($values) ? $values : func_get_args());
    }

    /**
     * Get a required_if rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Fabricate\Validation\Rules\RequiredIf
     */
    public static function requiredIf($callback)
    {
        return new RequiredIf($callback);
    }

    /**
     * Get a required_unless rule builder instance.
     *
     * @param  (\Closure(): bool)|bool|null  $callback
     * @return \Fabricate\Validation\Rules\RequiredUnless
     */
    public static function requiredUnless($callback)
    {
        return new RequiredUnless($callback);
    }

    /**
     * Get an exclude_if rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Fabricate\Validation\Rules\ExcludeIf
     */
    public static function excludeIf($callback)
    {
        return new ExcludeIf($callback);
    }

    /**
     * Get an exclude_unless rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Fabricate\Validation\Rules\ExcludeUnless
     */
    public static function excludeUnless($callback)
    {
        return new ExcludeUnless($callback);
    }

    /**
     * Get a prohibited_if rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Fabricate\Validation\Rules\ProhibitedIf
     */
    public static function prohibitedIf($callback)
    {
        return new ProhibitedIf($callback);
    }

    /**
     * Get a prohibited_unless rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Fabricate\Validation\Rules\ProhibitedUnless
     */
    public static function prohibitedUnless($callback)
    {
        return new ProhibitedUnless($callback);
    }

    /**
     * Get a date rule builder instance.
     *
     * @return \Fabricate\Validation\Rules\Date
     */
    public static function date()
    {
        return new Date;
    }

    /**
     * Get a datetime rule builder instance.
     */
    public static function dateTime(): Date
    {
        return (new Date)->format('Y-m-d H:i:s');
    }

    /**
     * Get an email rule builder instance.
     *
     * @return \Fabricate\Validation\Rules\Email
     */
    public static function email()
    {
        return new Email;
    }

    /**
     * Get an enum rule builder instance.
     *
     * @param  class-string  $type
     * @return \Fabricate\Validation\Rules\Enum
     */
    public static function enum($type)
    {
        return new Enum($type);
    }

    /**
     * Get a file rule builder instance.
     *
     * @return \Fabricate\Validation\Rules\File
     */
    public static function file()
    {
        return new File;
    }

    /**
     * Get an image file rule builder instance.
     *
     * @param  bool  $allowSvg
     * @return \Fabricate\Validation\Rules\ImageFile
     */
    public static function imageFile($allowSvg = false)
    {
        return new ImageFile($allowSvg);
    }

    /**
     * Get a dimensions rule builder instance.
     *
     * @param  array  $constraints
     * @return \Fabricate\Validation\Rules\Dimensions
     */
    public static function dimensions(array $constraints = [])
    {
        return new Dimensions($constraints);
    }

    /**
     * Get a string rule builder instance.
     *
     * @return \Fabricate\Validation\Rules\StringRule
     */
    public static function string()
    {
        return new StringRule;
    }

    /**
     * Get a numeric rule builder instance.
     *
     * @return \Fabricate\Validation\Rules\Numeric
     */
    public static function numeric()
    {
        return new Numeric;
    }

    /**
     * Get an "any of" rule builder instance.
     *
     * @param  array  $rules
     * @return \Fabricate\Validation\Rules\AnyOf
     *
     * @throws \InvalidArgumentException
     */
    public static function anyOf($rules)
    {
        return new AnyOf($rules);
    }

    /**
     * Get a contains rule builder instance.
     *
     * @param  \Fabricate\NutsAndBolts\Contracts\Arrayable|\UnitEnum|array|string  $values
     * @return \Fabricate\Validation\Rules\Contains
     */
    public static function contains($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new Rules\Contains(is_array($values) ? $values : func_get_args());
    }

    /**
     * Get a "does not contain" rule builder instance.
     *
     * @param  \Fabricate\NutsAndBolts\Contracts\Arrayable|\UnitEnum|array|string  $values
     * @return \Fabricate\Validation\Rules\DoesntContain
     */
    public static function doesntContain($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new Rules\DoesntContain(is_array($values) ? $values : func_get_args());
    }

    /**
     * Compile a set of rules for an attribute.
     *
     * @param  string  $attribute
     * @param  array  $rules
     * @param  array|null  $data
     * @return object|\stdClass
     */
    public static function compile($attribute, $rules, $data = null)
    {
        $parser = new ValidationRuleParser(
            Arr::undot(Arr::wrap($data))
        );

        if (is_array($rules) && ! array_is_list($rules)) {
            $nested = [];

            foreach ($rules as $key => $rule) {
                $nested[$attribute.'.'.$key] = $rule;
            }

            $rules = $nested;
        } else {
            $rules = [$attribute => $rules];
        }

        return $parser->explode(ValidationRuleParser::filterConditionalRules($rules, $data));
    }
}
