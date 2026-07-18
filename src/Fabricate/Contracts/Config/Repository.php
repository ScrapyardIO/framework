<?php

namespace Fabricate\Contracts\Config;

interface Repository
{
    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get the specified configuration value.
     *
     * @param array|string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(array|string $key, mixed $default = null): mixed;

    /**
     * Get all of the configuration items for the application.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Set a given configuration value.
     *
     * @param array|string $key
     * @param mixed|null $value
     * @return void
     */
    public function set(array|string $key, mixed $value = null): void;

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param string $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend(string $key, mixed $value): void;

    /**
     * Push a value onto an array configuration value.
     *
     * @param string $key
     * @param  mixed  $value
     * @return void
     */
    public function push(string $key, mixed $value): void;
}
