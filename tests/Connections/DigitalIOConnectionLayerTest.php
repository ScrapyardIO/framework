<?php

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeDigitalInputTransport;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeDigitalIOConnectionDriver;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeDigitalIOConnectionFactory;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeDigitalOutputTransport;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeHandle;

function digitalDriver(): FakeDigitalIOConnectionDriver
{
    $driver = new FakeDigitalIOConnectionDriver;
    $driver->connectTo(0)->register();

    return $driver;
}

it('connects a chip through a factory and registers the handle it opens', function (): void {
    $driver = new FakeDigitalIOConnectionDriver;

    $factory = $driver->connectTo(0);

    expect($factory)->toBeInstanceOf(FakeDigitalIOConnectionFactory::class)
        ->and($factory->device)->toBe(0)
        ->and($factory->register())->toBe($driver)
        ->and($driver->connections->get(0))->toBeInstanceOf(FakeHandle::class);
});

it('refuses to connect a chip that is already connected', function (): void {
    $driver = digitalDriver();

    expect(fn () => $driver->connectTo(0))->toThrow(DigitalIOException::class, 'Device 0 already connected');
});

it('returns null for pins on a chip that was never connected', function (): void {
    $driver = new FakeDigitalIOConnectionDriver;

    expect($driver->output(0, 17))->toBeNull()
        ->and($driver->input(0, 17))->toBeNull();
});

it('hands out an output pin on the chip handle', function (): void {
    $driver = digitalDriver();

    $pin = $driver->output(0, 17);

    expect($pin)->toBeInstanceOf(FakeDigitalOutputTransport::class)
        ->and($pin->pin)->toBe(17)
        ->and($pin->handle)->toBe($driver->connections->get(0));
});

it('high() and low() are write(true) and write(false)', function (): void {
    $pin = digitalDriver()->output(0, 17);

    $pin->high();
    $pin->low();
    $pin->high();

    expect($pin->writes)->toBe([true, false, true])
        ->and($pin->read())->toBeTrue()
        ->and($pin->write(false))->toBeFalse();
});

it('hands out an input pin as-is and active-high unless told otherwise', function (): void {
    $pin = digitalDriver()->input(0, 4);

    expect($pin)->toBeInstanceOf(FakeDigitalInputTransport::class)
        ->and($pin->pin)->toBe(4)
        ->and($pin->bias)->toBe(LineBias::AS_IS)
        ->and($pin->active_low)->toBeFalse();
});

it('passes bias and polarity through to the input pin', function (): void {
    $button = digitalDriver()->input(0, 5, LineBias::PULL_UP, true);

    expect($button->bias)->toBe(LineBias::PULL_UP)
        ->and($button->active_low)->toBeTrue();
});

it('drains pending edges through the input contract, filtered by subscription', function (): void {
    $pin = digitalDriver()->input(0, 5);
    $pin->pending = [
        new DigitalEdgeEvent(SignalEdge::RISING, 1_000),
        new DigitalEdgeEvent(SignalEdge::FALLING, 2_000),
    ];

    $falling = $pin->pollEdges(false, true);

    expect($falling)->toHaveCount(1)
        ->and($falling[0]->edge)->toBe(SignalEdge::FALLING)
        ->and($pin->pollEdges(true, true))->toBe([]);
});

it('closing a pin marks the chip handle closed', function (): void {
    $driver = digitalDriver();

    $driver->output(0, 17)->close();

    expect($driver->connections->get(0)->closed)->toBeTrue();
});
