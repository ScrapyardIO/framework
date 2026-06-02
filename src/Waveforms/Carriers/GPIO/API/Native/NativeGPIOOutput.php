<?php

namespace Waveforms\Carriers\GPIO\API\Native;

use Microscrap\Bindings\GPIO\Enums\GPIOV2LineFlag;
use Waveforms\Carriers\GPIO\Contracts\GPIOOutput;

class NativeGPIOOutput extends NativeGPIOLine implements GPIOOutput
{
    public array $flags = [
        'input' => GPIOV2LineFlag::OUTPUT,
    ];
}
