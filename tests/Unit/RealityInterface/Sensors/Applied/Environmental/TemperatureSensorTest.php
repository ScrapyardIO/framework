<?php

use RealityInterface\Sensors\Applied\Environmental\TemperatureSensor;
use RealityInterface\Sensors\Applied\Environmental\TemperatureSensorReading;
use RealityInterface\Sensors\Attributes\MeasuresTemperature;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Enums\TemperatureUnit;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

#[MeasuresTemperature(SensorType::TEMPERATURE)]
class FakeTempChip extends SensorChip
{
    public function __construct(private ?float $c = 25.0) {}

    public function temperature(): ?float
    {
        return $this->c;
    }
}

class FakeChipWithoutTemperatureAttribute extends SensorChip {}

#[MeasuresTemperature(SensorType::TEMPERATURE)]
class FakeContractCompliantTempChip extends SensorChip
{
    public function __construct(private ?float $c = 25.0) {}

    public function getTemperature(): ?float
    {
        return $this->c;
    }
}

it('returns celsius temperature as a passthrough', function () {
    $sensor = TemperatureSensor::as(new FakeTempChip(25.0));

    expect($sensor->temperature(TemperatureUnit::CELSIUS))->toBe(25.0);
});

it('converts celsius to fahrenheit', function () {
    $sensor = TemperatureSensor::as(new FakeTempChip(0.0));

    expect($sensor->temperature(TemperatureUnit::FAHRENHEIT))->toBe(32.0);
});

it('converts celsius to kelvin', function () {
    $sensor = TemperatureSensor::as(new FakeTempChip(0.0));

    expect($sensor->temperature(TemperatureUnit::KELVIN))->toBe(273.15);
});

it('throws SensorException when the chip returns null', function () {
    $sensor = TemperatureSensor::as(new FakeTempChip(null));

    $sensor->temperature();
})->throws(SensorException::class, 'Temperature reading is unavailable.');

it('returns a TemperatureSensorReading from measure with value units and timestamp', function () {
    $before = time();
    $sensor = TemperatureSensor::as(new FakeTempChip(20.0));

    $reading = $sensor->measure(TemperatureUnit::CELSIUS);
    $after = time();

    expect($reading)->toBeInstanceOf(TemperatureSensorReading::class)
        ->and($reading->value)->toBe(20.0)
        ->and($reading->units)->toBe(TemperatureUnit::CELSIUS)
        ->and($reading->timestamp)->toBeGreaterThanOrEqual($before)
        ->and($reading->timestamp)->toBeLessThanOrEqual($after);
});

it('throws SensorException when as() is called on a chip without MeasuresTemperature', function () {
    TemperatureSensor::as(new FakeChipWithoutTemperatureAttribute);
})->throws(SensorException::class)->todo();

it('reads via temperature() but the chip contract declares getTemperature()', function () {
    $sensor = TemperatureSensor::as(new FakeContractCompliantTempChip(25.0));

    expect($sensor->temperature())->toBe(25.0);
})->todo('Applied TemperatureSensor calls temperature() while contract declares getTemperature().');
