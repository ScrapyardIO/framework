<?php

namespace Fabricate\Pipeline;

use Closure;
use Fabricate\Chassis\Contracts\WireframeServiceContainer;
use Fabricate\Contracts\Pipeline\Hub as HubContract;

class Hub implements HubContract
{
    /**
     * The container implementation.
     *
     * @var WireframeServiceContainer|null
     */
    protected ?WireframeServiceContainer $container;

    /**
     * All of the available pipelines.
     *
     * @var array
     */
    protected array $pipelines = [];

    /**
     * Create a new Hub instance.
     *
     * @param  WireframeServiceContainer|null  $container
     */
    public function __construct(?WireframeServiceContainer $container = null)
    {
        $this->container = $container;
    }

    /**
     * Define the default named pipeline.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function defaults(Closure $callback): void
    {
        $this->pipeline('default', $callback);
    }

    /**
     * Define a new named pipeline.
     *
     * @param  string  $name
     * @param  Closure  $callback
     * @return void
     */
    public function pipeline($name, Closure $callback): void
    {
        $this->pipelines[$name] = $callback;
    }

    /**
     * Send an object through one of the available pipelines.
     *
     * @param  mixed  $object
     * @param  string|null  $pipeline
     * @return mixed
     */
    public function pipe(mixed $object, ?string $pipeline = null): mixed
    {
        $pipeline = $pipeline ?: 'default';

        return call_user_func(
            $this->pipelines[$pipeline], new Pipeline($this->container), $object
        );
    }

    /**
     * Get the container instance used by the hub.
     *
     * @return WireframeServiceContainer|null
     */
    public function getContainer(): ?WireframeServiceContainer
    {
        return $this->container;
    }

    /**
     * Set the container instance used by the hub.
     *
     * @param  WireframeServiceContainer  $container
     * @return $this
     */
    public function setContainer(WireframeServiceContainer $container): static
    {
        $this->container = $container;

        return $this;
    }
}
