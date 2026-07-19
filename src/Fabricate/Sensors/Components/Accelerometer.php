<?php

namespace Fabricate\Sensors\Components;

use Fabricate\Contracts\Sensors\AccelerationMeasurements;
use Fabricate\Contracts\Sensors\Enums\AxisOrientation;
use Fabricate\Contracts\Sensors\Enums\SpatialAxis;
use Fabricate\Contracts\Sensors\SensorException;
use Fabricate\IntegratedCircuits\SensorIC;
use Fabricate\Sensors\SensorComponent;

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
class Accelerometer extends SensorComponent
{
    protected bool $enabled = true;

    public function __construct(
        protected AccelerationMeasurements $circuit
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

    /**
     * @throws SensorException
     */
    public function getPitch(): float
    {
        return rad2deg(atan2($this->getX(), hypot($this->getY(), $this->getZ())));
    }

    /**
     * @throws SensorException
     */
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

        return $this->circuit->x();
    }

    /**
     * @throws SensorException
     */
    public function getY(): float
    {
        $this->ensureEnabled();

        return $this->circuit->y();
    }

    /**
     * @throws SensorException
     */
    public function getZ(): float
    {
        $this->ensureEnabled();

        return $this->circuit->z();
    }

    /**
     * @throws SensorException
     */
    public function getAcceleration(): float
    {
        $x = $this->getX();
        $y = $this->getY();
        $z = $this->getZ();

        return sqrt($x ** 2 + $y ** 2 + $z ** 2);
    }

    /**
     * @throws SensorException
     */
    public function getInclination(): float
    {
        return rad2deg(atan2($this->getY(), $this->getX()));
    }

    /**
     * @throws SensorException
     */
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

    /**
     * @throws SensorException
     */
    public static function buildWith(SensorIC $sensor): static
    {
        if($sensor instanceof AccelerationMeasurements) {
            return new static($sensor);
        }

        throw new SensorException($sensor::class.' must implement TemperatureMeasurements');
    }
}
