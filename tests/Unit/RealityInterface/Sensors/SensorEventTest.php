<?php

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

it('defaults sensor_type to DUMMY and measurement to NO_OP', function () {
    $event = new SensorEvent;

    expect($event->sensor_type)->toBe(SensorType::DUMMY)
        ->and($event->measurement)->toBe(SensorMeasurement::NO_OP);
});

it('stores explicit sensor_type and measurement on readonly props', function () {
    $event = new SensorEvent(SensorType::TEMPERATURE, SensorMeasurement::TEMPERATURE);

    expect($event->sensor_type)->toBe(SensorType::TEMPERATURE)
        ->and($event->measurement)->toBe(SensorMeasurement::TEMPERATURE);
});
