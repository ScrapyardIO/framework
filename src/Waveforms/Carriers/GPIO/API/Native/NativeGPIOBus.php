<?php

namespace Waveforms\Carriers\GPIO\API\Native;

use Microscrap\Bindings\GPIO\DataObjects\GPIOChip;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;
use Waveforms\Carriers\GPIO\Exceptions\GPIOException;
use Waveforms\Carriers\GPIO\GPIOBus;

class NativeGPIOBus extends GPIOBus
{
    public function __construct(
        public readonly GPIOChip $chip,
        public readonly GPIOLineRequest $line_request,
        public readonly array $lines = [],
    ) {}

    public function __call(string $name, array $arguments): NativeGPIOPanel
    {
        if (isset($this->lines[$name])) {
            return new NativeGPIOPanel($this->line_request, $this->lines[$name]->line);
        }

        throw GPIOException::pinAliasNotFound($name);
    }
}
