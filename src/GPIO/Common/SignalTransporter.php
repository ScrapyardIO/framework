<?php

namespace GPIO\Common;

use GPIO\Contracts\Common\GenericSignalTransporter;

abstract class SignalTransporter implements GenericSignalTransporter
{
    protected int $max_packet_size;

    public function __construct(
        public readonly string $active_transport
    ) {}

    abstract protected function detectTransport(): string;

    public function maxPacketSize(int $size): static
    {
        $this->max_packet_size = $size;
        return $this;
    }


}
