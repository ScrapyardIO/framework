<?php

namespace GeneralPurposeIO\I2C;

use Closure;
use Throwable;
use Voyager\NutsAndBolts\Collection;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Contracts\IOPools\WorkTarget;
use Voyager\IOPools\WorkTargets\QueueTarget;
use GeneralPurposeIO\NutsAndBolts\BusQueue;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\I2C\I2CTransport;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;
use GeneralPurposeIO\Contracts\Core\GPIOLevelException;

abstract class I2CConnectionDriver
{
    public readonly Collection $connections;

    /** @var array<string, I2CTransport> "<device>:<address>" => transport */
    protected array $transports = [];

    /** @var array<string, BusQueue> queueKey() => the one-at-a-time queue of offloaded jobs */
    private array $queues = [];

    private ?Closure $loop_resolver = null;

    private ?Closure $target_resolver = null;

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function getTransport(string|int $device, int $slave_address): I2CTransport;

    abstract protected function newConnection(int|string $device): I2CConnectionFactory;

    /** Close what connectTo() opened for one bus. Its slaves are already closed. */
    abstract protected function closeConnection(mixed $handle): void;

    public function register(string $name, mixed $handle): static
    {
        $this->connections->put($name, $handle);
        return $this;
    }

    public function connectTo(int|string $device): I2CConnectionFactory
    {
        if($this->connections->has($device)) {
            throw new I2CException("Device {$device} already connected");
        }

        return $this->newConnection($device);
    }

    /** Wire-internal: the manager hands every driver the closure that finds the event loop, or null. */
    public function resolvesLoopWith(Closure $resolver): static
    {
        $this->loop_resolver = $resolver;
        return $this;
    }

    /** Wire-internal: the manager hands every driver the closure that turns a target name (or null) into a WorkTarget. */
    public function resolvesTargetsWith(Closure $resolver): static
    {
        $this->target_resolver = $resolver;
        return $this;
    }

    /** One transport per slave per bus; a closed one is replaced by a fresh one. */
    public function device(string|int $device, int $slave_address): ?I2CTransport
    {
        if(! $this->connections->has($device)) {
            return null;
        }

        $key = "{$device}:{$slave_address}";
        $transport = $this->transports[$key] ?? null;

        if(is_null($transport) || $transport->closed()) {
            $transport = $this->transports[$key] = $this->getTransport($device, $slave_address)->attachTo($this, $device);
        }

        return $transport;
    }

    /** Close every slave on the bus, then the bus itself. connectTo() can open it again. */
    public function disconnect(string|int $device): void
    {
        foreach ($this->transports as $key => $transport) {
            if (str_starts_with($key, "{$device}:")) {
                $transport->close();
                unset($this->transports[$key]);
            }
        }

        foreach (array_keys($this->queues) as $key) {
            if ($key === (string) $device || str_starts_with($key, "{$device}:")) {
                unset($this->queues[$key]);
            }
        }

        if ($this->connections->has($device)) {
            $this->closeConnection($this->connections->get($device));
            $this->connections->forget($device);
        }
    }

    /** Queue $job for one slave; it starts once every earlier job on the same queue has settled. */
    public function offload(string|int $device, int $address, BusJob $job, ?string $target = null): Promise
    {
        $loop = $this->eventLoop() ?? throw I2CException::noEventLoop();
        $queue = $this->queues[$this->queueKey($device, $address)] ??= new BusQueue($loop);

        return $queue->push($address, fn (): Promise => $this->dispatch($device, $address, $job, $target, $loop, $queue));
    }

    /** Blocking calls wait here until the slave's queue has run dry. */
    public function drain(string|int $device, int $address): void
    {
        ($this->queues[$this->queueKey($device, $address)] ?? null)?->drain();
    }

    /** close(): the slave's queued jobs are rejected, its running one finishes. */
    public function abandon(string|int $device, int $address): void
    {
        ($this->queues[$this->queueKey($device, $address)] ?? null)?->abandon($address, I2CException::transportClosed($address));
    }

    /** Which queue a slave's jobs share. i2c-dev makes each transfer atomic, so slaves queue apart. */
    protected function queueKey(string|int $device, int $address): string
    {
        return "{$device}:{$address}";
    }

    /**
     * Where a job runs: a BusGig to the named work target, or to the configured one. An in-process target (sync,
     * defer) runs the gig on this process's own worker-side driver, with its own bus handle, as a worker would.
     */
    protected function dispatch(string|int $device, int $address, BusJob $job, ?string $target, Loop $loop, BusQueue $queue): Promise
    {
        $targets = $this->target_resolver ?? throw I2CException::noWorkTargets();

        /** @var WorkTarget $work */
        $work = $targets($target);

        // a queue target resolves with the queued job, so the promise could never mirror the blocking call
        if ($work instanceof QueueTarget) {
            throw I2CException::offloadTargetDiscardsResult($target ?? 'default');
        }

        return $work->run(new BusGig(static::class, $device, $address, $job))
            ->error(fn (Throwable $e) => throw GPIOLevelException::localize($e));
    }

    protected function eventLoop(): ?Loop
    {
        return is_null($this->loop_resolver) ? null : ($this->loop_resolver)();
    }
}
