<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital;

use GPIO\Digital\DigitalPinConnectionHandle;

/**
 * GPIO\Digital\DigitalPinConnectionHandle is abstract (it exists purely to
 * be typed against), so tests need a concrete instance to construct pins
 * and buses with. This fixture adds no behavior of its own.
 */
class FakeDigitalPinConnectionHandle extends DigitalPinConnectionHandle {}
