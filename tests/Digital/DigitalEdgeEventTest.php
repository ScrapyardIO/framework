<?php

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use Voyager\Contracts\IOPools\Event;

it('is loop mail named after its device and pin', function () {
    $edge = new DigitalEdgeEvent(4, 27, SignalEdge::RISING, 123_456_789, 3);

    expect($edge)->toBeInstanceOf(Event::class)
        ->and($edge->name())->toBe('gpio.edge.4.27')
        ->and($edge->uuid())->toBe('gpio.edge.4.27.123456789.3');
});

it('crosses a wire through toData() and fromData()', function () {
    $edge = new DigitalEdgeEvent('ft232h', 6, SignalEdge::FALLING, 42, 9);

    $data = $edge->toData();
    $back = DigitalEdgeEvent::fromData(json_decode(json_encode($data), true));

    expect($data['edge'])->toBe('falling')
        ->and($back)->toEqual($edge)
        ->and($back->edge)->toBe(SignalEdge::FALLING);
});
