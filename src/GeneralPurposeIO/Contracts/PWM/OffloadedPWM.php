<?php

namespace GeneralPurposeIO\Contracts\PWM;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use Voyager\Contracts\IOPools\Promise;

/** A channel's calls off the calling thread: each promise settles with what the blocking call returns or throws. */
interface OffloadedPWM
{
    /** @return Promise<int> */
    public function getPeriod(): Promise;

    /** @return Promise<int> */
    public function setPeriod(int $value): Promise;

    /** @return Promise<bool> */
    public function getEnable(): Promise;

    /** @return Promise<bool> */
    public function setEnable(bool $value): Promise;

    /** @return Promise<int> */
    public function getDutyCycle(): Promise;

    /** @return Promise<int> */
    public function setDutyCycle(int $value): Promise;

    /** @return Promise<bool> */
    public function getPolarity(): Promise;

    /** @return Promise<bool> */
    public function setPolarity(bool $value): Promise;

    /** @return Promise<mixed> */
    public function run(BusJob $job): Promise;
}
