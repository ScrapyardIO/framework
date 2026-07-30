<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

/**
 * A single contact in a multi-touch sample.
 *
 * Normalized x/y values use 0.0-1.0; pixel values use device coordinates.
 */
final readonly class TouchContact
{
    public function __construct(
        public int|string $id,
        public float $x,
        public float $y,
        public TouchPhase $phase,
        public CoordinateSpace $space = CoordinateSpace::NORMALIZED,
        public float $pressure = 1.0,
        public ?int $timestamp_ns = null,
    ) {}
}
