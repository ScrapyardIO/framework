<?php

namespace Fabricate\Console\View\Components\Mutators;

use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use ReflectionException;

class EnsureRelativePaths
{
    /**
     * Ensures the given string only contains relative paths.
     *
     * @param string $string
     * @return string
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
     */
    public function __invoke(string $string): string
    {
        if (function_exists('app') && app()->has('path.base')) {
            $string = str_replace(base_path().'/', '', $string);
        }

        return $string;
    }
}
