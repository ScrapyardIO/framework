<?php

use BareMetal\Actuation\Servos\ContinuousServo;
use BareMetal\Actuation\Servos\PositionalServo;
use BareMetal\Contracts\Actuators\Servos\CircularMotion;
use BareMetal\Contracts\Actuators\Servos\ClosedLoopMotor;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Actuation\FakeCircularMotion;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Actuation\FakeClosedLoopMotor;

test('PositionalServo implements ClosedLoopMotor and exposes the wrapped actuator', function () {
    $motor = new FakeClosedLoopMotor;
    $servo = new PositionalServo($motor);

    expect($servo)->toBeInstanceOf(ClosedLoopMotor::class)
        ->and($servo->actuator())->toBe($motor);
});

test('PositionalServo forwards shared ClosedLoopMotor methods', function () {
    $motor = new FakeClosedLoopMotor;
    $servo = new PositionalServo($motor);

    $servo->to(45, 100, 5);
    $servo->pulse(1_200_000);
    $servo->calibrate(900, 2100, 1500);
    $servo->enable();
    $enabled = $servo->enabled();
    $position = $servo->getPosition();
    $servo->disable();

    expect($enabled)->toBeTrue()
        ->and($position)->toBe(45)
        ->and($motor->is_enabled)->toBeFalse()
        ->and($motor->pulse_ns)->toBe(1_200_000)
        ->and($motor->calibration)->toBe(['min' => 900, 'max' => 2100, 'stop' => 1500])
        ->and($motor->calls)->toContain(['to', [45, 100, 5]])
        ->and($motor->calls)->toContain(['pulse', [1_200_000]])
        ->and($motor->calls)->toContain(['calibrate', [900, 2100, 1500]])
        ->and($motor->calls)->toContain(['enable', []])
        ->and($motor->calls)->toContain(['disable', []]);
});

test('PositionalServo forwards positional ClosedLoopMotor methods', function () {
    $motor = new FakeClosedLoopMotor;
    $servo = new PositionalServo($motor);

    $servo->center(50, 2);
    $servo->home();
    $servo->min();
    $servo->max();
    $servo->sweep(10, 170, [20, 160], 500, 5);

    expect($motor->calls)->toContain(['center', [50, 2]])
        ->and($motor->calls)->toContain(['home', []])
        ->and($motor->calls)->toContain(['min', []])
        ->and($motor->calls)->toContain(['max', []])
        ->and($motor->calls)->toContain(['sweep', [10, 170, [20, 160], 500, 5]]);
});

test('PositionalServo::calibrate returns the component for fluency', function () {
    $servo = new PositionalServo(new FakeClosedLoopMotor);

    expect($servo->calibrate(1000, 2000))->toBe($servo);
});

test('ContinuousServo implements CircularMotion and exposes the wrapped actuator', function () {
    $motor = new FakeCircularMotion;
    $servo = new ContinuousServo($motor);

    expect($servo)->toBeInstanceOf(CircularMotion::class)
        ->and($servo)->toBeInstanceOf(ClosedLoopMotor::class)
        ->and($servo->actuator())->toBe($motor);
});

test('ContinuousServo forwards circular motion methods', function () {
    $motor = new FakeCircularMotion;
    $servo = new ContinuousServo($motor);

    $servo->clockwise(80);
    $servo->counterClockwise(40);
    $servo->cw(70);
    $servo->ccw(30);
    $servo->stop();
    $returned = $servo->deadband(85, 95);

    expect($returned)->toBe($servo)
        ->and($motor->deadband)->toBe(['lower' => 85, 'upper' => 95])
        ->and($motor->calls)->toContain(['clockwise', [80]])
        ->and($motor->calls)->toContain(['counterClockwise', [40]])
        ->and($motor->calls)->toContain(['cw', [70]])
        ->and($motor->calls)->toContain(['ccw', [30]])
        ->and($motor->calls)->toContain(['stop', []])
        ->and($motor->calls)->toContain(['deadband', [85, 95]]);
});

test('ContinuousServo forwards ClosedLoopMotor methods through ServoComponent', function () {
    $motor = new FakeCircularMotion;
    $servo = new ContinuousServo($motor);

    $servo->to(120);
    $servo->enable();
    $servo->center();
    $servo->home();
    $servo->min();
    $servo->max();
    $servo->sweep(0, 180);

    expect($motor->calls)->toContain(['to', [120, 0, 0]])
        ->and($motor->calls)->toContain(['enable', []])
        ->and($motor->calls)->toContain(['center', [0, 0]])
        ->and($motor->calls)->toContain(['home', []])
        ->and($motor->calls)->toContain(['min', []])
        ->and($motor->calls)->toContain(['max', []])
        ->and($motor->calls)->toContain(['sweep', [0, 180, [], 1000, 10]]);
});
