<?php

use RealityInterface\Sensors\Applied\AmbientLighting\AmbientLightSensor;
use RealityInterface\Sensors\Attributes\MeasuresLuminance;
use RealityInterface\Sensors\Enums\LuminanceUnit;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

#[MeasuresLuminance(SensorType::LUX)]
class FakeLuxChip extends SensorChip
{
    public function __construct(private int|float $lux = 100) {}

    public function getLuminance(): int|float
    {
        return $this->lux;
    }
}

class FakeChipWithoutLuminanceAttribute extends SensorChip {}

it('returns itself from units and sets the luminance unit', function () {
    $sensor = AmbientLightSensor::as(new FakeLuxChip(100));

    $result = $sensor->units(LuminanceUnit::FOOTCANDLE);

    expect($result)->toBe($sensor)
        ->and($sensor->units)->toBe(LuminanceUnit::FOOTCANDLE);
});

it('sets compensation factors from reference and measured lux values', function () {
    $sensor = AmbientLightSensor::as(new FakeLuxChip(100));

    $result = $sensor->calibrate(100.0, 80.0);

    expect($result)->toBe($sensor)
        ->and($sensor->lux_compensation_factor)->toBe(1.25)
        ->and($sensor->fc_compensation_factor)->toBe(1.25);
});

it('returns raw lux multiplied by the lux compensation factor', function () {
    $sensor = AmbientLightSensor::as(new FakeLuxChip(500));

    expect($sensor->getLuminance())->toBe(500.0);
});

it('returns footcandles as raw lux divided by 10.764 times the fc compensation factor', function () {
    $sensor = AmbientLightSensor::as(new FakeLuxChip(107.64))
        ->units(LuminanceUnit::FOOTCANDLE);

    expect($sensor->getLuminance())->toEqualWithDelta(10.0, 0.0001);
});

it('wraps a MeasuresLuminance chip via as()', function () {
    $chip = new FakeLuxChip(100);

    $sensor = AmbientLightSensor::as($chip);

    expect($sensor)->toBeInstanceOf(AmbientLightSensor::class);
});

it('throws SensorException when as() is called on a chip without MeasuresLuminance', function () {
    AmbientLightSensor::as(new FakeChipWithoutLuminanceAttribute);
})->throws(SensorException::class)->todo();
