<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital;

use GPIO\Digital\MultipleDigitalPins;

/**
 * Returned by the fake digital driver adapters below so tests can inspect
 * exactly which arguments create() forwarded to buildConnection().
 *
 * Extends MultipleDigitalPins (rather than only implementing the
 * DigitalPinTransport/DigitalPinBus marker interfaces) because the
 * concrete factories' create() methods declare a strict
 * `DigitalInput|MultipleDigitalPins` (or DigitalOutput|MultipleDigitalPins)
 * return type - a plain marker-interface implementation would fail that
 * type check at runtime.
 */
class FakeDigitalConnectionBus extends MultipleDigitalPins
{
    public function __construct(public array $arguments = [])
    {
        parent::__construct([]);
    }
}
