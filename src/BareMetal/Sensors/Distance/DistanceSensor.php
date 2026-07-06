<?php

namespace BareMetal\Sensors\Distance;

use BareMetal\Contracts\Sensors\Distance\DistanceMeasurable as DistanceMeasurableInterface;
use BareMetal\Contracts\Sensors\Distance\DistanceSensor as DistanceSensorContract;
use BareMetal\Contracts\Sensors\Distance\DistanceUnit;
use BareMetal\Sensors\SensorComponent;

class DistanceSensor extends SensorComponent implements DistanceSensorContract
{
    public function __construct(
        protected DistanceMeasurableInterface $sensor
    ) {}

    public function distance(DistanceUnit $unit): float
    {
        return $this->sensor->distance($unit);
    }

    /**
     * There's no event loop backing this contract yet, so unlike
     * johnny-five's non-blocking listener registration, this blocks the
     * calling thread and keeps polling $this->sensor for as long as the
     * reading stays out of range, invoking $callback every time a poll
     * lands inside [low, high]. Return `false` from $callback to stop
     * polling and let this method return.
     */
    public function within(array $range, DistanceUnit $unit, callable $callback): void
    {
        [$low, $high] = $range;

        while (true) {
            $value = $this->distance($unit);

            if ($value >= $low && $value <= $high) {
                if ($callback($value) === false) {
                    return;
                }
            }

            usleep(100_000);
        }
    }

    public function measure(int $num_readings = 1, DistanceUnit $unit = DistanceUnit::MM): ?ProximityData
    {
        if ($num_readings < 1) {
            return null;
        }

        $total = 0.0;
        for ($i = 0; $i < $num_readings; $i++) {
            $total += $this->distance($unit);
        }

        return new ProximityData($total / $num_readings, $unit, microtime(true));
    }
}
