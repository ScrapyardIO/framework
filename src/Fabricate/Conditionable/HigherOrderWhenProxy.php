<?php

namespace Fabricate\NutsAndBolts;

class HigherOrderWhenProxy
{
    /**
     * The condition for proxying.
     */
    protected bool $condition = false;

    /**
     * Indicates whether the proxy has a condition.
     */
    protected bool $has_condition = false;

    /**
     * Determine whether the condition should be negated.
     */
    protected bool $negate_condition_on_capture = false;

    /**
     * Create a new proxy instance.
     */
    public function __construct(
        protected mixed $target = null
    ) {}

    /**
     * Set the condition on the proxy.
     */
    public function condition(bool $condition): static
    {
        [$this->condition, $this->has_condition] = [$condition, true];

        return $this;
    }

    /**
     * Indicate that the condition should be negated.
     */
    public function negateConditionOnCapture(): static
    {
        $this->negate_condition_on_capture = true;

        return $this;
    }

    /**
     * Proxy accessing an attribute onto the target.
     */
    public function __get(string $key): object
    {
        if (! $this->has_condition) {
            $condition = $this->target->{$key};

            return $this->condition($this->negate_condition_on_capture ? ! $condition : $condition);
        }

        return $this->condition
            ? $this->target->{$key}
            : $this->target;
    }

    /**
     * Proxy a method call on the target.
     */
    public function __call(string $method, array $parameters): object
    {
        if (! $this->has_condition) {
            $condition = $this->target->{$method}(...$parameters);

            return $this->condition($this->negate_condition_on_capture ? ! $condition : $condition);
        }

        return $this->condition
            ? $this->target->{$method}(...$parameters)
            : $this->target;
    }
}
