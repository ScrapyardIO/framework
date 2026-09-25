<?php

namespace GeneralPurposeIO\Contracts\I2C;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use Voyager\Contracts\IOPools\Promise;

/** A slave's calls off the calling thread: each promise settles with what the blocking call returns or throws. */
interface OffloadedI2C
{
    /** @return Promise<int> */
    public function write(array|string $data): Promise;

    /** @return Promise<array|false> */
    public function read(int $len): Promise;

    /** @return Promise<array|false> */
    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): Promise;

    /** @return Promise<array|false> */
    public function bulkWrite(array|string $messages): Promise;

    /** @return Promise<mixed> */
    public function run(BusJob $job): Promise;
}
