<?php

use RealityInterface\Sensors\Applied\Environmental\RelativeHumidityReading;
use RealityInterface\Sensors\Applied\Environmental\RelativeHumiditySensor;
use RealityInterface\Sensors\Attributes\MeasuresRelativeHumidity;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

#[MeasuresRelativeHumidity(SensorType::RELATIVE_HUMIDITY)]
class FakeHumidityChip extends SensorChip
{
    public function __construct(private ?float $percent = 55.0) {}

    public function getHumidity(): ?float
    {
        return $this->percent;
    }
}

class FakeChipWithoutRelativeHumidityAttribute extends SensorChip {}

it('returns relative humidity as a passthrough', function () {
    $sensor = RelativeHumiditySensor::as(new FakeHumidityChip(55.0));

    expect($sensor->humidity())->toBe(55.0);
});

it('throws SensorException when the chip returns null', function () {
    $sensor = RelativeHumiditySensor::as(new FakeHumidityChip(null));

    $sensor->humidity();
})->throws(SensorException::class, 'Relative humidity reading is unavailable.');

it('returns a RelativeHumidityReading from measure with percent set', function () {
    $before = time();
    $sensor = RelativeHumiditySensor::as(new FakeHumidityChip(42.5));

    $reading = $sensor->measure();
    $after = time();

    expect($reading)->toBeInstanceOf(RelativeHumidityReading::class)
        ->and($reading->percent)->toBe(42.5)
        ->and($reading->timestamp)->toBeGreaterThanOrEqual($before)
        ->and($reading->timestamp)->toBeLessThanOrEqual($after);
});

it('throws SensorException when as() is called on a chip without MeasuresRelativeHumidity', function () {
    RelativeHumiditySensor::as(new FakeChipWithoutRelativeHumidityAttribute);
})->throws(SensorException::class)->todo();
