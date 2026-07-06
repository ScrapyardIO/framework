<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\PWM;

use GPIO\PWM\PWMConnectionHandle;

/**
 * GPIO\PWM\PWMConnectionHandle is abstract and carries no behavior of its
 * own - it's a pure marker - so tests need a concrete instance to construct
 * PWM channels with.
 */
class FakePWMConnectionHandle extends PWMConnectionHandle {}
