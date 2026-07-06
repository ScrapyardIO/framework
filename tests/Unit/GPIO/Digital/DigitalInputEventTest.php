<?php

use GPIO\Contracts\Digital\DigitalInputEvent as DigitalInputEventContract;
use GPIO\Contracts\Digital\EdgeEvent;
use GPIO\Digital\Input\DigitalInputEvent;

test('it stores the event and an integer timestamp', function () {
    $event = new DigitalInputEvent(EdgeEvent::RISING, 12345);

    expect($event)->toBeInstanceOf(DigitalInputEventContract::class)
        ->and($event->event)->toBe(EdgeEvent::RISING)
        ->and($event->timestamp)->toBe(12345);
});

test('it stores the event and a float timestamp', function () {
    $event = new DigitalInputEvent(EdgeEvent::FALLING, 12345.6789);

    expect($event->event)->toBe(EdgeEvent::FALLING)
        ->and($event->timestamp)->toBe(12345.6789);
});

test('it is immutable', function () {
    $event = new DigitalInputEvent(EdgeEvent::RISING, 1);

    $event->timestamp = 2;
})->throws(Error::class);
