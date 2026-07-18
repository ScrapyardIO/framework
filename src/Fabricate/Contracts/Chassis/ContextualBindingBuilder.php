<?php

namespace Fabricate\Contracts\Chassis;

interface ContextualBindingBuilder
{
    /**
     * Define the abstract target that depends on the context.
     *
     * @param string $abstract
     * @return $this
     */
    public function needs(string $abstract): static;

    /**
     * Define the implementation for the contextual binding.
     *
     * @param array|\Closure|string $implementation
     * @return $this
     */
    public function give(array|callable|string $implementation): static;

    /**
     * Define tagged services to be used as the implementation for the contextual binding.
     *
     * @param string $tag
     * @return $this
     */
    public function giveTagged(string $tag): static;

    /**
     * Specify the configuration item to bind as a primitive.
     *
     * @param string $key
     * @param mixed|null $default
     * @return $this
     */
    public function giveConfig(string $key, mixed $default = null): static;
}
