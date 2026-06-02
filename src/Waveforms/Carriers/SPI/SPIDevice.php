<?php

namespace Waveforms\Carriers\SPI;

use Waveforms\WaveformCarrier;

abstract class SPIDevice extends WaveformCarrier
{
    abstract public function transfer(string|array $data): array|false;

    abstract public function read(int $len): array|false;

    abstract public function write(string|array $data): int;

    abstract public function close(): void;
}
