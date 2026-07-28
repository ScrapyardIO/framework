<?php

namespace Fabricate\NutsAndBolts\Concerns;

use Fabricate\Contracts\Chassis\WireframeServiceContainer;
use Fabricate\NutsAndBolts\Fluent;

trait CapsuleManagerTrait
{
    /**
     * The current globally used instance.
     *
     * @var object
     */
    protected static $instance;

    /**
     * The container instance.
     *
     * @var \Fabricate\Contracts\Chassis\WireframeServiceContainer
     */
    protected $container;

    /**
     * Setup the IoC container instance.
     *
     * @param  \Fabricate\Contracts\Chassis\WireframeServiceContainer  $container
     * @return void
     */
    protected function setupContainer(Container $container)
    {
        $this->container = $container;

        if (! $this->container->bound('config')) {
            $this->container->instance('config', new Fluent);
        }
    }

    /**
     * Make this capsule instance available globally.
     *
     * @return void
     */
    public function setAsGlobal()
    {
        static::$instance = $this;
    }

    /**
     * Get the IoC container instance.
     *
     * @return \Fabricate\Contracts\Chassis\WireframeServiceContainer
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Fabricate\Contracts\Chassis\WireframeServiceContainer  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
