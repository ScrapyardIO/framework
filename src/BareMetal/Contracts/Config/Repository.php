<?php

namespace BareMetal\Contracts\Config;

interface Repository
{
    /**
     * Determine if the given configuration value exists.
     */
    public function has(string $key): bool;

    /**
     * Get the specified configuration value.
     */
    public function get(array|string $key, mixed $default = null): mixed;

    /**
     * Get every configuration item for the application.
     */
    public function all(): array;

    /**
     * Set a given configuration value.
     */
    public function set(array|string $key, mixed $value = null): void;

    /**
     * Prepend a value onto an array configuration value.
     */
    public function prepend(string $key, mixed $value): void;

    /**
     * Push a value onto an array configuration value.
     */
    public function push(string $key, mixed $value): void;
}
