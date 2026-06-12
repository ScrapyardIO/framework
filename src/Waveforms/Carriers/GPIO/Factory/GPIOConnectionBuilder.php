<?php

namespace Waveforms\Carriers\GPIO\Factory;

use Waveforms\Carriers\GPIO\Contracts\GPIOInput;
use Waveforms\Carriers\GPIO\Contracts\GPIOOutput;
use Waveforms\Carriers\GPIO\GPIOBus;
use Waveforms\Factory\CarrierFactory;

abstract class GPIOConnectionBuilder extends CarrierFactory
{
    public array $desired_gpio = [];

    public string $request_consumer = 'scrapyard-io';

    abstract public function firstly(int|string $chip_device): static;

    abstract public function boot(): GPIOBus;

    /**
     * Co-reside this GPIO bus on the connection a data carrier already opened.
     *
     * Native carriers expose GPIO through independent kernel device nodes
     * (/dev/gpiochipN vs /dev/spidevX.Y), so there is nothing to share and the
     * default is a no-op. USB carriers override this to reuse the carrier's
     * single MPSSE engine instead of claiming the FTDI device a second time.
     */
    public function shareConnectionWith(object $carrier): static
    {
        return $this;
    }

    abstract public function addInput(GPIOInput $input): static;

    abstract public function addOutput(GPIOOutput $output): static;

    public function consumer(string $name): static
    {
        $this->request_consumer = $name;

        return $this;
    }

    abstract public function connection(): string;
}
