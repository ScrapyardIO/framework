<?php

namespace Waveforms\Carriers\I2C;

use Waveforms\WaveformCarrier;

abstract class I2CDevice extends WaveformCarrier
{
    abstract public function read(int $len): array|false;

    abstract public function write(string|array $data): int;

    abstract public function readWrite(string|array $bytes_to_write, int $bytes_to_read): array|false;

    abstract public function close(): void;
}
