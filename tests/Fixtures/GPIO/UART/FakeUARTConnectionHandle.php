<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\UART;

use GPIO\UART\UARTConnectionHandle;

/**
 * GPIO\UART\UARTConnectionHandle is abstract and carries no behavior of its
 * own - it's a pure marker - so tests need a concrete instance to construct
 * UART transports with.
 */
class FakeUARTConnectionHandle extends UARTConnectionHandle {}
