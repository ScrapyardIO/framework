<?php

namespace Fabricate\Chassis\Concerns;

use Fabricate\Chassis\Exceptions\BindingResolutionException;
use Fabricate\Chassis\Exceptions\CircularDependencyException;
use Fabricate\Contracts\Chassis\ContextualAttribute;
use ReflectionAttribute;
use ReflectionException;

trait CallbackManagement
{
    /**
     * Every registered rebound callback.
     *
     * @var array[]
     */
    protected array $reboundCallbacks = [];

    /**
     * Every before resolving callback by class type.
     *
     * @var array[]
     */
    protected array $beforeResolvingCallbacks = [];

    /**
     * Every global before resolving callback.
     *
     * @var list<callable>
     */
    protected array $globalBeforeResolvingCallbacks = [];

    /**
     * Every global resolving callback.
     *
     * @var list<callable>
     */
    protected array $globalResolvingCallbacks = [];

    /**
     * Every resolving callback by class type.
     *
     * @var array<string, list<callable>>
     */
    protected array $resolvingCallbacks = [];

    /**
     * Every global after resolving callback.
     *
     * @var list<callable>
     */
    protected array $globalAfterResolvingCallbacks = [];

    /**
     * Every after resolving callback by class type.
     *
     * @var array<string, list<callable>>
     */
    protected array $afterResolvingCallbacks = [];

    /**
     * Every after resolving attribute callback by class type.
     *
     * @var array<string, list<callable>>
     */
    protected array $afterResolvingAttributeCallbacks = [];

    /**
     * Fire the "rebound" callbacks for the given abstract type.
     *
     * @param string $abstract
     * @return void
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
     */
    protected function rebound(string $abstract): void
    {
        if (! $callbacks = $this->getReboundCallbacks($abstract)) {
            return;
        }

        $instance = $this->make($abstract);

        foreach ($callbacks as $callback) {
            $callback($this, $instance);
        }
    }

    /**
     * Get the rebound callbacks for a given type.
     *
     * @param string $abstract
     * @return array
     */
    protected function getReboundCallbacks(string $abstract): array
    {
        return $this->reboundCallbacks[$abstract] ?? [];
    }

    /**
     * Fire every before resolving callbacks.
     *
     * @param string $abstract
     * @param array $parameters
     * @return void
     */
    protected function fireBeforeResolvingCallbacks(string $abstract, array $parameters = []): void
    {
        $this->fireBeforeCallbackArray($abstract, $parameters, $this->globalBeforeResolvingCallbacks);

        foreach ($this->beforeResolvingCallbacks as $type => $callbacks) {
            if ($type === $abstract || is_subclass_of($abstract, $type)) {
                $this->fireBeforeCallbackArray($abstract, $parameters, $callbacks);
            }
        }
    }

    /**
     * Fire an array of callbacks with an object.
     *
     * @param string $abstract
     * @param array $parameters
     * @param array $callbacks
     * @return void
     */
    protected function fireBeforeCallbackArray(string $abstract, array $parameters, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($abstract, $parameters, $this);
        }
    }

    /**
     * Fire every after resolving attribute callbacks.
     *
     * @param  ReflectionAttribute[]  $attributes
     * @param  mixed  $object
     * @return void
     */
    public function fireAfterResolvingAttributeCallbacks(array $attributes, mixed $object): void
    {
        foreach ($attributes as $attribute) {
            if (is_a($attribute->getName(), ContextualAttribute::class, true)) {
                $instance = $attribute->newInstance();

                if (method_exists($instance, 'after')) {
                    $instance->after($instance, $object, $this);
                }
            }

            $callbacks = $this->getCallbacksForType(
                $attribute->getName(), $object, $this->afterResolvingAttributeCallbacks
            );

            foreach ($callbacks as $callback) {
                $callback($attribute->newInstance(), $object, $this);
            }
        }
    }

    /**
     * Fire an array of callbacks with an object.
     *
     * @param mixed $object
     * @param array $callbacks
     * @return void
     */
    protected function fireCallbackArray(mixed $object, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * Fire every resolving callbacks.
     *
     * @param string $abstract
     * @param  mixed  $object
     * @return void
     */
    protected function fireResolvingCallbacks(string $abstract, mixed $object): void
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks)
        );

        $this->fireAfterResolvingCallbacks($abstract, $object);
    }

    /**
     * Fire every after resolving callbacks.
     *
     * @param string $abstract
     * @param  mixed  $object
     * @return void
     */
    protected function fireAfterResolvingCallbacks(string $abstract, mixed $object): void
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks)
        );
    }

    /**
     * Get all callbacks for a given type.
     *
     * @param string $abstract
     * @param mixed $object
     * @param array $callbacksPerType
     * @return array
     */
    protected function getCallbacksForType(string $abstract, mixed $object, array $callbacksPerType): array
    {
        $results = [];

        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstract || (is_object($object) && $object instanceof $type)) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }
}