<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\PWM;

use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;
use GPIO\Contracts\PWM\PWMChannelBus;
use GPIO\Contracts\PWM\PWMChannelTransport;
use GPIO\Contracts\PWM\PWMConnectionHandle as PWMConnectionHandleInterface;
use GPIO\Contracts\PWM\PWMDriverAdapter;
use GPIO\PWM\PWMChannel;

/**
 * A recording PWM driver adapter: every method call is captured so tests
 * can assert exactly what the factory/transport forwarded to the driver,
 * mirroring FakeI2CDriverAdapter, FakeSPIDriverAdapter and FakeUARTDriverAdapter.
 */
class FakePWMDriverAdapter implements PWMDriverAdapter
{
    /** Returned by buildConnection() unless overridden per-test. */
    public PWMChannelTransport|PWMChannelBus|null $buildConnectionReturnValue = null;

    /** Every argument list passed to buildConnection(). */
    public array $buildConnectionCalls = [];

    /** Value returned by every call to setDutyCycle(), unless overridden per-test. */
    public int $setDutyCycleReturnValue = 0;

    /** Every [$value, $handle] pair passed to setDutyCycle(). */
    public array $setDutyCycleCalls = [];

    /** Value returned by every call to getDutyCycle(), unless overridden per-test. */
    public int $getDutyCycleReturnValue = 0;

    /** Every handle passed to getDutyCycle(). */
    public array $getDutyCycleCalls = [];

    /** Value returned by every call to setPeriod(), unless overridden per-test. */
    public int $setPeriodReturnValue = 0;

    /** Every [$value, $handle] pair passed to setPeriod(). */
    public array $setPeriodCalls = [];

    /** Value returned by every call to getPeriod(), unless overridden per-test. */
    public int $getPeriodReturnValue = 0;

    /** Every handle passed to getPeriod(). */
    public array $getPeriodCalls = [];

    /** Value returned by every call to setEnable(), unless overridden per-test. */
    public bool $setEnableReturnValue = false;

    /** Every [$value, $handle] pair passed to setEnable(). */
    public array $setEnableCalls = [];

    /** Value returned by every call to getEnable(), unless overridden per-test. */
    public bool $getEnableReturnValue = false;

    /** Every handle passed to getEnable(). */
    public array $getEnableCalls = [];

    /** Value returned by every call to setPolarity(), unless overridden per-test. */
    public string $setPolarityReturnValue = 'normal';

    /** Every [$value, $handle] pair passed to setPolarity(). */
    public array $setPolarityCalls = [];

    /** Value returned by every call to getPolarity(), unless overridden per-test. */
    public string $getPolarityReturnValue = 'normal';

    /** Every handle passed to getPolarity(). */
    public array $getPolarityCalls = [];

    /** Every handle passed to close(). */
    public array $closeCalls = [];

    public function buildConnection(
        int|string $chip,
        int $channel,
        string $consumer,
        array $addl_channels = []
    ): PWMChannelTransport|PWMChannelBus {
        $this->buildConnectionCalls[] = [
            'chip' => $chip,
            'channel' => $channel,
            'consumer' => $consumer,
            'addl_channels' => $addl_channels,
        ];

        return $this->buildConnectionReturnValue ?? new PWMChannel(new FakePWMConnectionHandle, $this);
    }

    public function setDutyCycle(int $value, PWMConnectionHandleInterface $handle): int
    {
        $this->setDutyCycleCalls[] = [$value, $handle];

        return $this->setDutyCycleReturnValue;
    }

    public function getDutyCycle(PWMConnectionHandleInterface $handle): int
    {
        $this->getDutyCycleCalls[] = $handle;

        return $this->getDutyCycleReturnValue;
    }

    public function setPeriod(int $value, PWMConnectionHandleInterface $handle): int
    {
        $this->setPeriodCalls[] = [$value, $handle];

        return $this->setPeriodReturnValue;
    }

    public function getPeriod(PWMConnectionHandleInterface $handle): int
    {
        $this->getPeriodCalls[] = $handle;

        return $this->getPeriodReturnValue;
    }

    public function setEnable(bool $value, PWMConnectionHandleInterface $handle): bool
    {
        $this->setEnableCalls[] = [$value, $handle];

        return $this->setEnableReturnValue;
    }

    public function getEnable(PWMConnectionHandleInterface $handle): bool
    {
        $this->getEnableCalls[] = $handle;

        return $this->getEnableReturnValue;
    }

    public function setPolarity(string $value, PWMConnectionHandleInterface $handle): string
    {
        $this->setPolarityCalls[] = [$value, $handle];

        return $this->setPolarityReturnValue;
    }

    public function getPolarity(PWMConnectionHandleInterface $handle): string
    {
        $this->getPolarityCalls[] = $handle;

        return $this->getPolarityReturnValue;
    }

    public function close(GPIOConnectionHandleInterface $handle): void
    {
        $this->closeCalls[] = $handle;
    }
}
