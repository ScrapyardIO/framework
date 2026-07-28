<?php

namespace Fabricate\Chassis;

use Fabricate\Contracts\Chassis\WireframeServiceContainer;
use Fabricate\Contracts\Chassis\ContextualBindingBuilder as ContextualBindingBuilderContract;

class ContextualBindingBuilder implements ContextualBindingBuilderContract
{
    /**
     * The underlying container instance.
     *
     * @var WireframeServiceContainer
     */
    protected WireframeServiceContainer $container;

    /**
     * The concrete instance.
     *
     * @var string|array
     */
    protected string|array $concrete;

    /**
     * The abstract target.
     *
     * @var string
     */
    protected string $needs;

    /**
     * Create a new contextual binding builder.
     *
     * @param WireframeServiceContainer $container
     * @param array|string $concrete
     */
    public function __construct(WireframeServiceContainer $container, array|string $concrete)
    {
        $this->concrete = $concrete;
        $this->container = $container;
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * @param string $abstract
     * @return $this
     */
    public function needs(string $abstract): static
    {
        $this->needs = $abstract;

        return $this;
    }

    /**
     * Define the implementation for the contextual binding.
     *
     * @param  \Closure|string|array  $implementation
     * @return $this
     */
    public function give($implementation): static
    {
        foreach (Util::arrayWrap($this->concrete) as $concrete) {
            $this->container->addContextualBinding($concrete, $this->needs, $implementation);
        }

        return $this;
    }

    /**
     * Define tagged services to be used as the implementation for the contextual binding.
     *
     * @param string $tag
     * @return $this
     */
    public function giveTagged(string $tag): static
    {
        return $this->give(function ($container) use ($tag) {
            $taggedServices = $container->tagged($tag);

            return is_array($taggedServices) ? $taggedServices : iterator_to_array($taggedServices);
        });
    }

    /**
     * Specify the configuration item to bind as a primitive.
     *
     * @param string $key
     * @param mixed|null $default
     * @return $this
     */
    public function giveConfig(string $key, mixed $default = null): static
    {
        return $this->give(fn ($container) => $container->get('config')->get($key, $default));
    }
}
