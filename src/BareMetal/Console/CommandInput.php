<?php

namespace BareMetal\Console;

use ScrapyardIO\NutsAndBolts\Arr;
use ScrapyardIO\NutsAndBolts\Concerns\Dumpable;

class CommandInput
{
    use Dumpable;

    /**
     * The command arguments.
     */
    protected array $arguments;

    /**
     * The command options.
     */
    protected array $options;

    /**
     * Create a new command input container.
     */
    public function __construct(array $arguments = [], array $options = [])
    {
        $this->arguments = $arguments;
        $this->options = $options;
    }

    /**
     * Get all of the input for the command.
     *
     * Options take precedence over arguments when keys collide.
     */
    public function all(mixed $keys = null): array
    {
        $input = array_merge($this->options, $this->arguments);

        if (! $keys) {
            return $input;
        }

        $results = [];

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            Arr::set($results, $key, Arr::get($input, $key));
        }

        return $results;
    }

    /**
     * Retrieve data from the instance.
     */
    protected function data(?string $key = null, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    /**
     * Determine if an input item exists.
     */
    public function exists(string $key): bool
    {
        return Arr::has($this->all(), $key);
    }

    /**
     * Get all of the arguments passed to the command.
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get all of the options passed to the command.
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return $this->all();
    }

    /**
     * Dynamically access input data.
     */
    public function __get(string $name): mixed
    {
        return $this->data($name);
    }

    /**
     * Determine if an input item is set.
     */
    public function __isset(string $name): bool
    {
        return $this->exists($name);
    }
}
