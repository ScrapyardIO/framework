<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Contracts\I2C\OffloadedI2C;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\NutsAndBolts\TransportCall;
use Voyager\Contracts\IOPools\Promise;

/** What via() hands back: the slave's calls as jobs on its driver's queue. Refused once the slave closes. */
final class OffloadedI2CTransport implements OffloadedI2C
{
    public function __construct(
        private readonly I2CTransport $transport,
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

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): Promise
    {
        return $this->run(new TransportCall('writeRead', [$bytes_to_write, $bytes_to_read]));
    }

    public function bulkWrite(array|string $messages): Promise
    {
        return $this->run(new TransportCall('bulkWrite', [$messages]));
    }

    public function run(BusJob $job): Promise
    {
        return $this->transport->offload($job, $this->target);
    }
}
