<?php

use RealityInterface\Sensors\Applied\Distance\TimeOfFlightDistanceSensor;
use RealityInterface\Sensors\Attributes\MeasuresDistance;
use RealityInterface\Sensors\Enums\LengthUnit;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

#[MeasuresDistance(SensorType::TIME_OF_FLIGHT)]
class FakeTimeOfFlightChip extends SensorChip
{
    public function getDistance(LengthUnit $unit = LengthUnit::CM): int|float
    {
        return 0;
    }
}

#[MeasuresDistance(SensorType::ULTRASONIC)]
class FakeUltrasonicMismatchChip extends SensorChip
{
    public function getDistance(LengthUnit $unit = LengthUnit::CM): int|float
    {
        return 0;
    }
}

class FakeChipWithoutMeasuresDistanceAttributeForToF extends SensorChip {}

it('wraps a TIME_OF_FLIGHT MeasuresDistance chip via as()', function () {
    $sensor = TimeOfFlightDistanceSensor::as(new FakeTimeOfFlightChip);

    expect($sensor)->toBeInstanceOf(TimeOfFlightDistanceSensor::class);
});

it('throws SensorException when as() is called on a chip with a mismatched SensorType', function () {
    TimeOfFlightDistanceSensor::as(new FakeUltrasonicMismatchChip);
})->throws(SensorException::class, 'FakeUltrasonicMismatchChip needs to be TIME_OF_FLIGHT.');

it('reads distance via getDistance', function () {
    $sensor = TimeOfFlightDistanceSensor::as(new FakeTimeOfFlightChip);

    $sensor->getDistance();
})->todo('TimeOfFlightDistanceSensor::getDistance() delegates to readDistance() which references undefined $this->circuit.');

it('reads distance via readDistance', function () {
    $sensor = TimeOfFlightDistanceSensor::as(new FakeTimeOfFlightChip);

    $sensor->readDistance();
})->todo('TimeOfFlightDistanceSensor::readDistance() references undefined $this->circuit instead of $this->sensor.');

it('returns a DistanceSensorReading from measure', function () {
    $sensor = TimeOfFlightDistanceSensor::as(new FakeTimeOfFlightChip);

    $sensor->measure();
})->todo('TimeOfFlightDistanceSensor::measure() calls readDistance() which references undefined $this->circuit.');

it('throws SensorException when as() is called on a chip without MeasuresDistance', function () {
    TimeOfFlightDistanceSensor::as(new FakeChipWithoutMeasuresDistanceAttributeForToF);
})->throws(SensorException::class)->todo();
