<?php

use RealityInterface\Sensors\Applied\Distance\UltrasonicDistanceSensor;
use RealityInterface\Sensors\Attributes\MeasuresDistance;
use RealityInterface\Sensors\Enums\LengthUnit;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

#[MeasuresDistance(SensorType::ULTRASONIC)]
class FakeUltrasonicChip extends SensorChip
{
    public function __construct(private int|float $distance = 1000.0) {}

    public function getDistance(LengthUnit $unit = LengthUnit::CM): int|float
    {
        return $this->distance;
    }
}

#[MeasuresDistance(SensorType::TIME_OF_FLIGHT)]
class FakeTimeOfFlightMismatchChip extends SensorChip
{
    public function getDistance(LengthUnit $unit = LengthUnit::CM): int|float
    {
        return 0;
    }
}

class FakeChipWithoutMeasuresDistanceAttributeForUltrasonic extends SensorChip {}

#[MeasuresDistance(SensorType::ULTRASONIC)]
class FakeUltrasonicUnitTrackingChip extends SensorChip
{
    public function __construct(private int|float $distance = 250.0) {}

    public function getDistance(LengthUnit $unit = LengthUnit::CM): int|float
    {
        expect($unit)->toBe(LengthUnit::FT);

        return $this->distance;
    }
}

it('passes configured units through to the chip from getDistance', function () {
    $sensor = UltrasonicDistanceSensor::as(new FakeUltrasonicUnitTrackingChip(250.0));
    $sensor->units(LengthUnit::FT);

    expect($sensor->getDistance())->toBe(250.0);
});

it('converts a centimeter-source reading to the configured length unit via readDistance', function (LengthUnit $unit, float $expected, float $delta) {
    $sensor = UltrasonicDistanceSensor::as(new FakeUltrasonicChip(1000.0));
    $sensor->units($unit);

    expect($sensor->readDistance())->toEqualWithDelta($expected, $delta);
})->with([
    'centimeters' => [LengthUnit::CM, 100.0, 0.0],
    'meters' => [LengthUnit::M, 1.0, 0.0],
    'inches' => [LengthUnit::IN, 1000.0 / 25.4, 0.001],
    'feet' => [LengthUnit::FT, 1000.0 / 304.8, 0.001],
    'yards' => [LengthUnit::YD, 1000.0 / 914.4, 0.001],
]);

it('wraps an ULTRASONIC MeasuresDistance chip via as()', function () {
    $sensor = UltrasonicDistanceSensor::as(new FakeUltrasonicChip);

    expect($sensor)->toBeInstanceOf(UltrasonicDistanceSensor::class);
});

it('throws SensorException when as() is called on a chip with a mismatched SensorType', function () {
    UltrasonicDistanceSensor::as(new FakeTimeOfFlightMismatchChip);
})->throws(SensorException::class, 'FakeTimeOfFlightMismatchChip needs to be ULTRASONIC.');

it('returns a DistanceSensorReading from measure', function () {
    $sensor = UltrasonicDistanceSensor::as(new FakeUltrasonicChip);

    $sensor->measure();
})->todo('UltrasonicDistanceSensor::measure() calls undefined $this->pulse().');

it('throws SensorException when as() is called on a chip without MeasuresDistance', function () {
    UltrasonicDistanceSensor::as(new FakeChipWithoutMeasuresDistanceAttributeForUltrasonic);
})->throws(SensorException::class)->todo();
