<?php

namespace Fabricate\Contracts\Sketches;

interface SketchRegistry
{
    /**
     * Register an attributed Sketch class (package or config-loaded).
     *
     * @param  class-string  $class
     */
    public function register(string $class): void;

    /**
     * Register a conventionally discovered app Sketch under an explicit name.
     *
     * @param  class-string  $class
     */
    public function registerConvention(string $name, string $class): void;

    /**
     * Register or overwrite an attributed Sketch (companions upgrading a package smoke test).
     *
     * @param  class-string  $class
     */
    public function replace(string $class): void;

    /**
     * Register or overwrite a Sketch under an explicit name.
     *
     * @param  class-string  $class
     */
    public function replaceAs(string $name, string $class): void;

    /**
     * Resolve a registered Sketch through the container.
     */
    public function resolve(string $name): Sketch;

    /**
     * Determine whether a sketch name is registered.
     */
    public function has(string $name): bool;

    /**
     * @return array<string, class-string>
     */
    public function all(): array;
}
