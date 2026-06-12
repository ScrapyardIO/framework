<?php

use RealityInterface\Sensors\Applied\Environmental\BarometricPressureReading;
use RealityInterface\Sensors\Applied\Environmental\RelativeHumidityReading;
use RealityInterface\Sensors\Applied\Environmental\TemperatureSensorReading;
use RealityInterface\Sensors\Enums\PressureUnit;
use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Enums\TemperatureUnit;

it('stores value units and timestamp on TemperatureSensorReading', function () {
    $reading = new TemperatureSensorReading(22.5, TemperatureUnit::CELSIUS, 1_700_000_000);

    expect($reading->value)->toBe(22.5)
        ->and($reading->units)->toBe(TemperatureUnit::CELSIUS)
        ->and($reading->timestamp)->toBe(1_700_000_000);
});

it('sets parent sensor_type and measurement on TemperatureSensorReading', function () {
    $reading = new TemperatureSensorReading(0, TemperatureUnit::KELVIN, 0);

    expect($reading->sensor_type)->toBe(SensorType::TEMPERATURE)
        ->and($reading->measurement)->toBe(SensorMeasurement::TEMPERATURE);
});

it('stores value units and timestamp on BarometricPressureReading', function () {
    $reading = new BarometricPressureReading(101_325, PressureUnit::PA, 1_700_000_001);

    expect($reading->value)->toBe(101_325)
        ->and($reading->units)->toBe(PressureUnit::PA)
        ->and($reading->timestamp)->toBe(1_700_000_001);
});

it('sets parent sensor_type and measurement on BarometricPressureReading', function () {
    $reading = new BarometricPressureReading(0, PressureUnit::HPA, 0);

    expect($reading->sensor_type)->toBe(SensorType::BAROMETER)
        ->and($reading->measurement)->toBe(SensorMeasurement::BAROMETRIC_PRESSURE);
});

it('stores percent and timestamp on RelativeHumidityReading', function () {
    $reading = new RelativeHumidityReading(55.0, 1_700_000_002);

    expect($reading->percent)->toBe(55.0)
        ->and($reading->timestamp)->toBe(1_700_000_002);
});

it('sets parent sensor_type and measurement on RelativeHumidityReading', function () {
    $reading = new RelativeHumidityReading(0, 0);

    expect($reading->sensor_type)->toBe(SensorType::RELATIVE_HUMIDITY)
        ->and($reading->measurement)->toBe(SensorMeasurement::RELATIVE_HUMIDITY);
});
