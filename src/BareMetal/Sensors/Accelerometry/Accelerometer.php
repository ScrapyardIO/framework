<?php

namespace BareMetal\Sensors\Accelerometry;

use BareMetal\Contracts\Sensors\Accelerometry\AxisOrientation;
use BareMetal\Contracts\Sensors\Accelerometry\SpatialAxis;
use BareMetal\Contracts\Sensors\SensorException;
use BareMetal\Sensors\SensorComponent;
use BareMetal\Contracts\Sensors\Accelerometry\Accelerometer as AccelerometerContract;
use BareMetal\Contracts\Sensors\Accelerometry\AccelerationMeasurable as AccelerationMeasurableInterface;

/**
 * @property-read float $pitch
 * @property-read float $roll
 * @property-read float $x
 * @property-read float $y
 * @property-read float $z
 * @property-read float $acceleration
 * @property-read float $inclination
 * @property-read AxisOrientation $orientation
 */
class Accelerometer extends SensorComponent implements AccelerometerContract
{
    protected bool $enabled = true;

    public function __construct(
        protected AccelerationMeasurableInterface $sensor
    ) {}

    /**
     * @throws SensorException
     */
    public function __get(string $name)
    {
        return match($name) {
            'pitch' => $this->getPitch(),
            'roll' => $this->getRoll(),
            'x' => $this->getX(),
            'y' => $this->getY(),
            'z' => $this->getZ(),
            'acceleration' => $this->getAcceleration(),
            'inclination' => $this->getInclination(),
            'orientation' => $this->getOrientation(),
            default => throw SensorException::invalidProperty($name, static::class)
        };
    }

    public function hasAxis(SpatialAxis $axis): bool
    {
        // AccelerationMeasurable mandates x()/y()/z() on every implementer,
        // so unlike johnny-five's flexible 2/3-pin analog boards, anything
        // wired through this contract always exposes all three axes.
        return true;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function getPitch(): float
    {
        return rad2deg(atan2($this->getX(), hypot($this->getY(), $this->getZ())));
    }

    public function getRoll(): float
    {
        return rad2deg(atan2($this->getY(), hypot($this->getX(), $this->getZ())));
    }

    /**
     * @throws SensorException
     */
    public function getX(): float
    {
        $this->ensureEnabled();

        return $this->sensor->x();
    }

    /**
     * @throws SensorException
     */
    public function getY(): float
    {
        $this->ensureEnabled();

        return $this->sensor->y();
    }

    /**
     * @throws SensorException
     */
    public function getZ(): float
    {
        $this->ensureEnabled();

        return $this->sensor->z();
    }

    public function getAcceleration(): float
    {
        $x = $this->getX();
        $y = $this->getY();
        $z = $this->getZ();

        return sqrt($x ** 2 + $y ** 2 + $z ** 2);
    }

    public function getInclination(): float
    {
        return rad2deg(atan2($this->getY(), $this->getX()));
    }

    public function getOrientation(): AxisOrientation
    {
        $x = $this->getX();
        $y = $this->getY();
        $z = $this->getZ();

        // The axis with the smallest magnitude is the one closest to
        // perpendicular with gravity - i.e. the axis currently defining
        // the device's resting orientation.
        $magnitudes = ['x' => abs($x), 'y' => abs($y), 'z' => abs($z)];
        [$smallest_axis] = array_keys($magnitudes, min($magnitudes));

        return match ($smallest_axis) {
            'x' => $x >= 0 ? AxisOrientation::X : AxisOrientation::X_INVERTED,
            'y' => $y >= 0 ? AxisOrientation::Y : AxisOrientation::Y_INVERTED,
            'z' => $z >= 0 ? AxisOrientation::Z : AxisOrientation::Z_INVERTED,
        };
    }

    /**
     * @throws SensorException
     */
    private function ensureEnabled(): void
    {
        if (!$this->enabled) {
            throw SensorException::disabled(static::class);
        }
    }
}
