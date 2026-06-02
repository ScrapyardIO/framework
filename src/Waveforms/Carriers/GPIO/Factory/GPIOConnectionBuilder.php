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

    abstract public function addInput(GPIOInput $input): static;

    abstract public function addOutput(GPIOOutput $output): static;

    public function consumer(string $name): static
    {
        $this->request_consumer = $name;

        return $this;
    }

    abstract public function connection(): string;
}
