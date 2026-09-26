<?php

namespace GeneralPurposeIO\PWM;

use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\Contracts\PWM\OffloadedPWM;
use GeneralPurposeIO\NutsAndBolts\TransportCall;
use Voyager\Contracts\IOPools\Promise;

/** What via() hands back: the channel's calls as jobs on its driver's queue. Refused once the channel closes. */
final class OffloadedPWMTransport implements OffloadedPWM
{
    public function __construct(
        private readonly PWMTransport $transport,
        private readonly ?string $target,
    ) {}

    public function getPeriod(): Promise
    {
        return $this->run(new TransportCall('getPeriod'));
    }

    public function setPeriod(int $value): Promise
    {
        return $this->run(new TransportCall('setPeriod', [$value]));
    }

    public function getEnable(): Promise
    {
        return $this->run(new TransportCall('getEnable'));
    }

    public function setEnable(bool $value): Promise
    {
        return $this->run(new TransportCall('setEnable', [$value]));
    }

    public function getDutyCycle(): Promise
    {
        return $this->run(new TransportCall('getDutyCycle'));
    }

    public function setDutyCycle(int $value): Promise
    {
        return $this->run(new TransportCall('setDutyCycle', [$value]));
    }

    public function getPolarity(): Promise
    {
        return $this->run(new TransportCall('getPolarity'));
    }

    public function setPolarity(bool $value): Promise
    {
        return $this->run(new TransportCall('setPolarity', [$value]));
    }

    public function run(BusJob $job): Promise
    {
        return $this->transport->offload($job, $this->target);
    }
}
