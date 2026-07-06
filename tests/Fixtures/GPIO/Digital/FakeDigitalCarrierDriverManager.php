<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital;

use GPIO\Common\CarrierDriverManager;
use GPIO\Contracts\Digital\DigitalPinDriverAdapter as DigitalPinDriverAdapterInterface;

/**
 * A fake carrier whose digital-in/digital-out drivers record exactly what
 * they were asked to build, so DigitalPinConnectionFactory subclasses can be
 * unit-tested without any real hardware carrier.
 */
class FakeDigitalCarrierDriverManager extends CarrierDriverManager
{
    public function __construct()
    {
        parent::__construct('fake-digital');
    }

    protected function createDigitalInputDriver(): DigitalPinDriverAdapterInterface
    {
        return new FakeDigitalInputDriverAdapter;
    }

    protected function createDigitalOutputDriver(): DigitalPinDriverAdapterInterface
    {
        return new FakeDigitalOutputDriverAdapter;
    }
}
