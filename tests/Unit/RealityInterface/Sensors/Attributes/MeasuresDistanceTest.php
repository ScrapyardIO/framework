<?php

use RealityInterface\Sensors\Attributes\MeasuresDistance;
use RealityInterface\Sensors\Enums\SensorType;

it('stores the sensor type on the readonly sensor_type property', function (SensorType $sensorType) {
    $attribute = new MeasuresDistance($sensorType);

    expect($attribute->sensor_type)->toBe($sensorType);
})->with([
    'time of flight' => [SensorType::TIME_OF_FLIGHT],
    'ultrasonic' => [SensorType::ULTRASONIC],
    'generic distance' => [SensorType::DISTANCE],
]);
