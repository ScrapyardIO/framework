<?php

namespace Fabricate\NutsAndBolts\Concerns;

use Closure;
use Fabricate\NutsAndBolts\HigherOrderWhenProxy;

trait Conditionable
{
    /**
     * Apply the callback if the given "value" is (or resolves to) truthy.
     */
    public function when(mixed $value = null, ?callable $callback = null, ?callable $default = null): mixed
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (func_num_args() === 0) {
            return new HigherOrderWhenProxy($this);
        }

        if (func_num_args() === 1) {
            return new HigherOrderWhenProxy($this)->condition($value);
        }

        if ($value) {
            return $callback($this, $value) ?? $this;
        } elseif ($default) {
            return $default($this, $value) ?? $this;
        }

        return $this;
    }

    /**
     * Apply the callback if the given "value" is (or resolves to) falsy.
     */
    public function unless(mixed $value = null, ?callable $callback = null, ?callable $default = null): mixed
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (func_num_args() === 0) {
            return new HigherOrderWhenProxy($this)->negateConditionOnCapture();
        }

        if (func_num_args() === 1) {
            return new HigherOrderWhenProxy($this)->condition(! $value);
        }

        if (! $value) {
            return $callback($this, $value) ?? $this;
        } elseif ($default) {
            return $default($this, $value) ?? $this;
        }

        return $this;
    }
}
