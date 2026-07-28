<?php

namespace Fabricate\NutsAndBolts\Concerns;

use Fabricate\NutsAndBolts\HigherOrderTapProxy;

trait Tappable
{
    /**
     * Call the given Closure with this instance then return the instance.
     *
     * @param (callable($this): mixed)|null $callback
     * @return ($callback is null ? HigherOrderTapProxy : $this)
     */
    public function tap(?callable $callback = null): static|HigherOrderTapProxy
    {
        return tap($this, $callback);
    }
}