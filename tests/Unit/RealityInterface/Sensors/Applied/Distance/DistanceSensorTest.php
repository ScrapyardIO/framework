<?php

use RealityInterface\Sensors\Applied\Distance\DistanceSensor;
use RealityInterface\Sensors\Applied\Distance\DistanceSensorReading;
use RealityInterface\Sensors\Attributes\MeasuresDistance;
use RealityInterface\Sensors\Enums\LengthUnit;
use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

#[MeasuresDistance(SensorType::DISTANCE)]
class FakeDistanceChip extends SensorChip
{
    public function __construct(private int|float $distance = 42.0) {}

    public function getDistance(LengthUnit $unit = LengthUnit::CM): int|float
    {
        return $this->distance;
    }
}

class FakeChipWithoutMeasuresDistanceAttribute extends SensorChip {}

it('returns $this from units() and stores the configured unit', function () {
    $sensor = DistanceSensor::as(new FakeDistanceChip);

    $result = $sensor->units(LengthUnit::M);

    expect($result)->toBe($sensor)
        ->and($sensor->units)->toBe(LengthUnit::M);
});

it('returns the chip distance unchanged from getDistance', function () {
    $sensor = DistanceSensor::as(new FakeDistanceChip(123.5));

    expect($sensor->getDistance())->toBe(123.5);
});

it('returns a DistanceSensorReading from measure with sensor type measurement units and value', function () {
    $before = time();
    $sensor = DistanceSensor::as(new FakeDistanceChip(55.0));
    $sensor->units(LengthUnit::IN);
    $after = time();

    $reading = $sensor->measure();

    expect($reading)->toBeInstanceOf(DistanceSensorReading::class)
        ->and($reading->sensor_type)->toBe(SensorType::DISTANCE)
        ->and($reading->measurement)->toBe(SensorMeasurement::DISTANCE)
        ->and($reading->value)->toBe(55.0)
        ->and($reading->units)->toBe(LengthUnit::IN)
        ->and($reading->timestamp)->toBeGreaterThanOrEqual($before)
        ->and($reading->timestamp)->toBeLessThanOrEqual($after);
});

it('wraps a MeasuresDistance chip via as()', function () {
    $sensor = DistanceSensor::as(new FakeDistanceChip);

    expect($sensor)->toBeInstanceOf(DistanceSensor::class);
});

it('throws SensorException when as() is called on a chip without MeasuresDistance', function () {
    DistanceSensor::as(new FakeChipWithoutMeasuresDistanceAttribute);
})->throws(SensorException::class)->todo();
