<?php

namespace GeneralPurposeIO\SPI;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\Contracts\SPI\OffloadedSPI;
use GeneralPurposeIO\NutsAndBolts\TransportCall;
use Voyager\Contracts\IOPools\Promise;

/** What via() hands back: the slave's calls as jobs on its bus's queue. Refused once the slave closes. */
final class OffloadedSPITransport implements OffloadedSPI
{
    public function __construct(
        private readonly SPITransport $transport,
        private readonly ?string $target,
    ) {}

    public function write(array|string $data): Promise
    {
        return $this->run(new TransportCall('write', [$data]));
    }

    public function read(int $len): Promise
    {
        return $this->run(new TransportCall('read', [$len]));
    }

    public function transfer(array|string $data): Promise
    {
        return $this->run(new TransportCall('transfer', [$data]));
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): Promise
    {
        return $this->run(new TransportCall('writeRead', [$bytes_to_write, $bytes_to_read]));
    }

    public function run(BusJob $job): Promise
    {
        return $this->transport->offload($job, $this->target);
    }
}
