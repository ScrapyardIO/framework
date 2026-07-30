<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

use Fabricate\Contracts\Circuits\IntegratedCircuit;

/**
 * Transport-neutral single- or multi-touch input.
 */
interface Touch extends IntegratedCircuit
{
    public function poll(): static;

    /**
     * Return every active contact in the requested coordinate space.
     *
     * @return list<TouchContact>
     */
    public function contacts(CoordinateSpace $space = CoordinateSpace::NORMALIZED): array;

    public function primaryContact(CoordinateSpace $space = CoordinateSpace::NORMALIZED): ?TouchContact;
}
