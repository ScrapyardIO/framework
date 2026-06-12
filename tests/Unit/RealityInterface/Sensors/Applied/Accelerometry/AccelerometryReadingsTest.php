<?php

use RealityInterface\Sensors\Applied\Accelerometry\AccelerometerReading;
use RealityInterface\Sensors\Applied\Accelerometry\ActivityReading;
use RealityInterface\Sensors\Applied\Accelerometry\FreefallReading;
use RealityInterface\Sensors\Applied\Accelerometry\ImpactReading;
use RealityInterface\Sensors\Applied\Accelerometry\MagnitudeReading;
use RealityInterface\Sensors\Applied\Accelerometry\TiltReading;
use RealityInterface\Sensors\Applied\Accelerometry\VibrationReading;
use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;

it('stores props and parent type and measurement on AccelerometerReading', function () {
    $reading = new AccelerometerReading(0.1, 0.2, 0.9, 1_700_000_000);

    expect($reading->x)->toBe(0.1)
        ->and($reading->y)->toBe(0.2)
        ->and($reading->z)->toBe(0.9)
        ->and($reading->timestamp)->toBe(1_700_000_000)
        ->and($reading->sensor_type)->toBe(SensorType::ACCELEROMETER)
        ->and($reading->measurement)->toBe(SensorMeasurement::ACCELERATION);
});

it('stores props and parent type and measurement on TiltReading', function () {
    $reading = new TiltReading(10.0, -5.0, 0.1, 0.2, 0.9, 1_700_000_001);

    expect($reading->roll)->toBe(10.0)
        ->and($reading->pitch)->toBe(-5.0)
        ->and($reading->x)->toBe(0.1)
        ->and($reading->y)->toBe(0.2)
        ->and($reading->z)->toBe(0.9)
        ->and($reading->timestamp)->toBe(1_700_000_001)
        ->and($reading->sensor_type)->toBe(SensorType::ACCELEROMETER)
        ->and($reading->measurement)->toBe(SensorMeasurement::TILT);
});

it('stores props and parent type and measurement on MagnitudeReading', function () {
    $reading = new MagnitudeReading(1.02, 0.1, 0.2, 0.9, 1_700_000_002);

    expect($reading->magnitude)->toBe(1.02)
        ->and($reading->x)->toBe(0.1)
        ->and($reading->y)->toBe(0.2)
        ->and($reading->z)->toBe(0.9)
        ->and($reading->timestamp)->toBe(1_700_000_002)
        ->and($reading->sensor_type)->toBe(SensorType::ACCELEROMETER)
        ->and($reading->measurement)->toBe(SensorMeasurement::MAGNITUDE);
});

it('stores props and parent type and measurement on FreefallReading', function () {
    $reading = new FreefallReading(true, 0.05, 0.3, 0.0, 0.0, 0.05, 1_700_000_003);

    expect($reading->is_freefall)->toBeTrue()
        ->and($reading->magnitude)->toBe(0.05)
        ->and($reading->threshold)->toBe(0.3)
        ->and($reading->x)->toBe(0.0)
        ->and($reading->y)->toBe(0.0)
        ->and($reading->z)->toBe(0.05)
        ->and($reading->timestamp)->toBe(1_700_000_003)
        ->and($reading->sensor_type)->toBe(SensorType::ACCELEROMETER)
        ->and($reading->measurement)->toBe(SensorMeasurement::FREEFALL);
});

it('stores props and parent type and measurement on ImpactReading', function () {
    $reading = new ImpactReading(3.5, 1.0, 2.0, 3.0, 50, 1_700_000_004);

    expect($reading->peak_g)->toBe(3.5)
        ->and($reading->x)->toBe(1.0)
        ->and($reading->y)->toBe(2.0)
        ->and($reading->z)->toBe(3.0)
        ->and($reading->samples)->toBe(50)
        ->and($reading->timestamp)->toBe(1_700_000_004)
        ->and($reading->sensor_type)->toBe(SensorType::ACCELEROMETER)
        ->and($reading->measurement)->toBe(SensorMeasurement::IMPACT);
});

it('stores props and parent type and measurement on VibrationReading', function () {
    $reading = new VibrationReading(0.02, 100, 1_700_000_005);

    expect($reading->rms)->toBe(0.02)
        ->and($reading->samples)->toBe(100)
        ->and($reading->timestamp)->toBe(1_700_000_005)
        ->and($reading->sensor_type)->toBe(SensorType::ACCELEROMETER)
        ->and($reading->measurement)->toBe(SensorMeasurement::VIBRATION);
});

it('stores props and parent type and measurement on ActivityReading', function () {
    $reading = new ActivityReading(false, 0.01, 0.05, 25, 1_700_000_006);

    expect($reading->is_moving)->toBeFalse()
        ->and($reading->max_delta)->toBe(0.01)
        ->and($reading->threshold)->toBe(0.05)
        ->and($reading->samples)->toBe(25)
        ->and($reading->timestamp)->toBe(1_700_000_006)
        ->and($reading->sensor_type)->toBe(SensorType::ACCELEROMETER)
        ->and($reading->measurement)->toBe(SensorMeasurement::ACTIVITY);
});
