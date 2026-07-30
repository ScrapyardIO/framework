<?php

namespace DeptOfScrapyardRobotics\Tests\Actuation;

use Fabricate\Actuation\Fans\FanComponent;
use Fabricate\Contracts\Actuation\Interfaces\Fan;
use PHPUnit\Framework\TestCase;

class FanComponentTest extends TestCase
{
    public function testItForwardsPowerSpeedFrequencyAndClose(): void
    {
        $driver = new FakeFan;
        $fan = new FanComponent($driver);

        $fan->on();
        $this->assertSame(65, $fan->speed(65));
        $this->assertSame(25_000, $fan->frequency(25_000));
        $fan->off();
        $fan->close();

        $this->assertInstanceOf(Fan::class, $fan);
        $this->assertSame($driver, $fan->actuator());
        $this->assertFalse($driver->on);
        $this->assertSame(65, $fan->speed());
        $this->assertSame(25_000, $fan->frequency());
        $this->assertTrue($driver->closed);
    }
}

class FakeFan implements Fan
{
    public bool $on = false;

    public bool $closed = false;

    private int $speed = 0;

    private int $frequency = 0;

    public function on(): void
    {
        $this->on = true;
    }

    public function off(): void
    {
        $this->on = false;
    }

    public function speed(?int $percent = null): int
    {
        if (! is_null($percent)) {
            $this->speed = $percent;
        }

        return $this->speed;
    }

    public function frequency(?int $hz = null): int
    {
        if (! is_null($hz)) {
            $this->frequency = $hz;
        }

        return $this->frequency;
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
