<?php

namespace Waveforms\Carriers\GPIO\API\Native;

use Microscrap\Bindings\GPIO\Enums\GPIOV2LineFlag;
use Waveforms\Carriers\GPIO\Concerns\NonblockingGPIO;
use Waveforms\Carriers\GPIO\Contracts\GPIOInput;

class NativeGPIOInput extends NativeGPIOLine implements GPIOInput
{
    use NonblockingGPIO;

    public array $flags = [
        'input' => GPIOV2LineFlag::INPUT,
    ];

    public function edgeEvents(): static
    {
        $this->flags['edge_rising'] = GPIOV2LineFlag::EDGE_RISING;
        $this->flags['edge_falling'] = GPIOV2LineFlag::EDGE_FALLING;

        return $this;
    }

    public function noEdgeEvents(): static
    {
        if (isset($this->flags['edge_rising'])) {
            unset($this->flags['edge_rising']);
        }

        if (isset($this->flags['edge_falling'])) {
            unset($this->flags['edge_falling']);
        }

        return $this;
    }

    public function risingEdgeEvents(): static
    {
        $this->flags['edge_rising'] = GPIOV2LineFlag::EDGE_RISING;

        return $this;
    }

    public function fallingEdgeEvents(): static
    {
        $this->flags['edge_falling'] = GPIOV2LineFlag::EDGE_FALLING;

        return $this;
    }
}
