<?php

use RealityInterface\Sensors\Applied\Distance\DistanceSensorReading;
use RealityInterface\Sensors\Enums\LengthUnit;
use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;

it('forwards the sensor_type argument to the parent SensorEvent', function (SensorType $sensorType) {
    $reading = new DistanceSensorReading($sensorType, 42.0, LengthUnit::CM, 1_700_000_000);

    expect($reading->sensor_type)->toBe($sensorType);
})->with([
    'time of flight' => [SensorType::TIME_OF_FLIGHT],
    'ultrasonic' => [SensorType::ULTRASONIC],
]);

it('sets measurement to DISTANCE on the parent SensorEvent', function () {
    $reading = new DistanceSensorReading(SensorType::DISTANCE, 0, LengthUnit::M, 0);

    expect($reading->measurement)->toBe(SensorMeasurement::DISTANCE);
});

it('stores value units and timestamp on DistanceSensorReading', function () {
    $reading = new DistanceSensorReading(SensorType::ULTRASONIC, 150.5, LengthUnit::MM, 1_700_000_001);

    expect($reading->value)->toBe(150.5)
        ->and($reading->units)->toBe(LengthUnit::MM)
        ->and($reading->timestamp)->toBe(1_700_000_001);
});
