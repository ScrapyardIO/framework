<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\PWM;

use GPIO\Common\CarrierDriverManager;
use GPIO\Contracts\PWM\PWMDriverAdapter as PWMDriverAdapterInterface;

/**
 * A fake carrier whose pwm driver records exactly what it was asked to
 * build, so PWMConnectionFactory can be unit-tested without any real
 * hardware carrier.
 */
class FakePWMCarrierDriverManager extends CarrierDriverManager
{
    public function __construct()
    {
        parent::__construct('fake-pwm');
    }

    protected function createPWMDriver(): PWMDriverAdapterInterface
    {
        return new FakePWMDriverAdapter;
    }
}
