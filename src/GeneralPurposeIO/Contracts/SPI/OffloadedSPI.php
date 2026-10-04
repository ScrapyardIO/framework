<?php

namespace GeneralPurposeIO\Contracts\SPI;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use Voyager\Contracts\IOPools\Promise;

/**
 * A slave's calls off the calling thread: each promise settles with what the blocking call returns or throws.
 * To hold chip select across several calls, run() a BusJob that calls select() on the slave it is handed.
 */
interface OffloadedSPI
{
    /** @return Promise<int> */
    public function write(array|string $data): Promise;

    /** @return Promise<array|false> */
    public function read(int $len): Promise;

    /** @return Promise<array|false> */
    public function transfer(array|string $data): Promise;

    /** @return Promise<array|false> */
    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): Promise;

    /** @return Promise<mixed> */
    public function run(BusJob $job): Promise;
}
