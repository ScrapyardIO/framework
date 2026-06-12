<?php

use RealityInterface\Sensors\Applied\Environmental\BarometricPressureReading;
use RealityInterface\Sensors\Applied\Environmental\BarometricPressureSensor;
use RealityInterface\Sensors\Attributes\MeasuresBarometricPressure;
use RealityInterface\Sensors\Enums\PressureUnit;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

#[MeasuresBarometricPressure(SensorType::BAROMETER)]
class FakePressureChip extends SensorChip
{
    public function __construct(private ?float $pa = 101_325.0) {}

    public function getPressure(): ?float
    {
        return $this->pa;
    }
}

class FakeChipWithoutBarometricPressureAttribute extends SensorChip {}

it('converts pascals to the requested pressure unit', function (PressureUnit $unit, float $expected, float $delta) {
    $sensor = BarometricPressureSensor::as(new FakePressureChip(101_325.0));

    expect($sensor->pressure($unit))->toEqualWithDelta($expected, $delta);
})->with([
    'pascals' => [PressureUnit::PA, 101_325.0, 0.0],
    'hectopascals' => [PressureUnit::HPA, 1013.25, 0.0],
    'millibars' => [PressureUnit::MBAR, 1013.25, 0.0],
    'atmospheres' => [PressureUnit::ATM, 1.0, 0.0],
    'mm mercury' => [PressureUnit::MM_MERCURY, 101_325.0 / 133.322, 0.001],
    'psi' => [PressureUnit::PSI, 101_325.0 / 6_894.757293168, 0.001],
]);

it('throws SensorException when the chip returns null', function () {
    $sensor = BarometricPressureSensor::as(new FakePressureChip(null));

    $sensor->pressure();
})->throws(SensorException::class, 'Barometric pressure reading is unavailable.');

it('returns a BarometricPressureReading from measure with value and units', function () {
    $before = time();
    $sensor = BarometricPressureSensor::as(new FakePressureChip(101_325.0));

    $reading = $sensor->measure(PressureUnit::HPA);
    $after = time();

    expect($reading)->toBeInstanceOf(BarometricPressureReading::class)
        ->and($reading->value)->toBe(1013.25)
        ->and($reading->units)->toBe(PressureUnit::HPA)
        ->and($reading->timestamp)->toBeGreaterThanOrEqual($before)
        ->and($reading->timestamp)->toBeLessThanOrEqual($after);
});

it('throws SensorException when as() is called on a chip without MeasuresBarometricPressure', function () {
    BarometricPressureSensor::as(new FakeChipWithoutBarometricPressureAttribute);
})->throws(SensorException::class)->todo();
