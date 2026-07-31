<?php

namespace Fabricate\Contracts\UX;

/**
 * A child that wants a share of whatever main-axis space a flex container has
 * left over once its inflexible children have been measured.
 *
 * An interface rather than a check for one concrete class, so a purpose-built
 * node can be flexible without having to be wrapped in an
 * {@see \Fabricate\UX\Layout\Expanded}.
 */
interface Flexible extends Node
{
    /**
     * This child's weight in the leftover-space split. Zero opts back out and is
     * measured as an ordinary child.
     */
    public function flex(): int;
}
