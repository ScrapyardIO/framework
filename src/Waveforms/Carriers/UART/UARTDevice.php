<?php

namespace Waveforms\Carriers\UART;

use Waveforms\WaveformCarrier;

abstract class UARTDevice extends WaveformCarrier
{
    abstract public function read(int $len): array|false;

    abstract public function write(string|array $data): int;

    abstract public function flush(): void;

    abstract public function close(): void;
}
