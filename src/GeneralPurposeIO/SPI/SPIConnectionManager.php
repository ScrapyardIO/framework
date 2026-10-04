<?php

namespace GeneralPurposeIO\SPI;

use InvalidArgumentException;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\WorkerPools\WorkerPool;
use Voyager\Contracts\Vessel\DataBindingException;
use Voyager\NutsAndBolts\Manager;

class SPIConnectionManager extends Manager
{
    public function createNoneDriver(): SPIConnectionDriver
    {
        return new NoneSPIConnectionDriver;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('gpio.protocols.spi.default', 'none');
    }

    /** Every driver, built in or extend()ed, looks the loop and the worker pools up when used, so provider order never matters. */
    protected function createDriver(string $driver): SPIConnectionDriver
    {
        return parent::createDriver($driver)
            ->resolvesLoopWith(fn (): ?Loop => $this->eventLoop())
            ->resolvesPoolsWith(fn (?string $pool): WorkerPool => $this->workerPool($pool));
    }

    private function eventLoop(): ?Loop
    {
        if (! $this->vessel->isBound(Loop::class)) {
            return null;
        }

        try {
            return $this->vessel->make(Loop::class);
        } catch (DataBindingException) {
            // the core alias can mark the loop bound before IOPools registers a concrete one
            return null;
        }
    }

    /**
     * The pool via() offloads to: 'thread' or 'process' by name, or with none named, the thread pool when it is on
     * and the process pool otherwise.
     *
     * @throws InvalidArgumentException the pool doesn't exist or isn't on
     */
    private function workerPool(?string $pool): WorkerPool
    {
        $binding = match ($pool) {
            null => $this->vessel->isBound('thread-workers') ? 'thread-workers' : 'process-workers',
            'thread' => 'thread-workers',
            'process' => 'process-workers',
            default => throw new InvalidArgumentException(
                "There is no \"{$pool}\" pool: offload to 'thread' or 'process', or name none for the thread pool when it is on and the process pool otherwise."
            ),
        };

        if (! $this->vessel->isBound($binding)) {
            throw new InvalidArgumentException(match (true) {
                is_null($pool) => 'Offloading runs on a worker pool, and none is on: enable io-pools.pool_workers.threads or io-pools.pool_workers.process.',
                $pool === 'thread' => 'The thread pool is off: enable io-pools.pool_workers.threads to offload to it.',
                default => 'The process pool is off: enable io-pools.pool_workers.process to offload to it.',
            });
        }

        return $this->vessel->make($binding);
    }
}
