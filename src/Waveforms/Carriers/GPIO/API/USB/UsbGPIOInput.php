<?php

namespace Waveforms\Carriers\GPIO\API\USB;

use Waveforms\Carriers\GPIO\Concerns\NonblockingGPIO;
use Waveforms\Carriers\GPIO\Contracts\GPIOInput;

class UsbGPIOInput extends UsbGPIOPin implements GPIOInput
{
    use NonblockingGPIO;

    public bool $emulate_edge_rising_events = false;

    public bool $emulate_edge_falling_events = true;

    public function edgeEvents(): static
    {
        $this->emulate_edge_falling_events = true;
        $this->emulate_edge_rising_events = true;

        return $this;
    }

    public function noEdgeEvents(): static
    {
        $this->emulate_edge_falling_events = false;
        $this->emulate_edge_rising_events = false;

        return $this;
    }

    public function risingEdgeEvents(): static
    {
        $this->emulate_edge_rising_events = true;

        return $this;
    }

    public function fallingEdgeEvents(): static
    {
        $this->emulate_edge_falling_events = true;

        return $this;
    }
}
