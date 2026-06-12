<?php

use RealityInterface\Sensors\Applied\Accelerometry\Accelerometer;
use RealityInterface\Sensors\Attributes\MeasuresAcceleration;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\SensorChip;

#[MeasuresAcceleration(SensorType::ACCELEROMETER)]
class FakeAccelChip extends SensorChip
{
    public function __construct(private array $acceleration = ['x' => 0.0, 'y' => 0.0, 'z' => 9.81]) {}

    public function getAcceleration(): array
    {
        return $this->acceleration;
    }
}

class FakeChipWithoutAccelerationAttribute extends SensorChip {}

it('returns the chip acceleration array unchanged from getAcceleration', function () {
    $acceleration = ['x' => 1.2, 'y' => -0.5, 'z' => 9.81];
    $sensor = Accelerometer::as(new FakeAccelChip($acceleration));

    expect($sensor->getAcceleration())->toBe($acceleration);
});

it('wraps a MeasuresAcceleration chip via as()', function () {
    $chip = new FakeAccelChip(['x' => 0.0, 'y' => 0.0, 'z' => 1.0]);

    $sensor = Accelerometer::as($chip);

    expect($sensor)->toBeInstanceOf(Accelerometer::class);
});

it('throws SensorException when as() is called on a chip without MeasuresAcceleration', function () {
    Accelerometer::as(new FakeChipWithoutAccelerationAttribute);
})->throws(SensorException::class)->todo();
