<?php

namespace GeneralPurposeIO\NutsAndBolts;

use Closure;
use Throwable;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Contracts\IOPools\ShouldPool;
use Voyager\Contracts\IOPools\WorkTarget;
use Voyager\IOPools\WorkTargets\QueueTarget;
use GeneralPurposeIO\Contracts\Core\GPIOLevelException;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;

/**
 * What a protocol driver needs for via(): the event loop and work targets the manager hands it, and one BusQueue per
 * queueKey(). An owner names a slave on its bus: an I2C address, an SPI chip select.
 */
trait OffloadsBusJobs
{
    /** @var array<string, BusQueue> queueKey() => the one-at-a-time queue of offloaded jobs */
    private array $queues = [];

    private ?Closure $loop_resolver = null;

    private ?Closure $target_resolver = null;

    /** @return class-string<GPIOLevelException> the protocol's exception, so a refusal names the protocol */
    abstract protected function protocolException(): string;

    /** What a closed slave's queued jobs are rejected with. */
    abstract protected function closedReason(int $owner): Throwable;

    /** Which queue a slave's jobs share. */
    abstract protected function queueKey(string|int $device, int $owner): string;

    /** Starts one job wherever it runs, and hands back its promise. */
    abstract protected function dispatch(string|int $device, int $owner, BusJob $job, ?string $target, Loop $loop, BusQueue $queue): Promise;

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

    /** Queue $job for one slave; it starts once every earlier job on the same queue has settled. */
    public function offload(string|int $device, int $owner, BusJob $job, ?string $target = null): Promise
    {
        $loop = $this->eventLoop() ?? throw ($this->protocolException())::noEventLoop();
        $queue = $this->queues[$this->queueKey($device, $owner)] ??= new BusQueue($loop);

        return $queue->push($owner, fn (): Promise => $this->dispatch($device, $owner, $job, $target, $loop, $queue));
    }

    /** Blocking calls wait here for the jobs queued on the slave's queue before them. */
    public function drain(string|int $device, int $owner): void
    {
        ($this->queues[$this->queueKey($device, $owner)] ?? null)?->drain();
    }

    /** close(): the slave's queued jobs are rejected, its running one finishes. */
    public function abandon(string|int $device, int $owner): void
    {
        ($this->queues[$this->queueKey($device, $owner)] ?? null)?->abandon($owner, $this->closedReason($owner));
    }

    /** disconnect(): the bus's queues go with it. Bus 1's never takes bus 11's along. */
    protected function forgetQueues(string|int $device): void
    {
        foreach (array_keys($this->queues) as $key) {
            if ($key === (string) $device) {
                unset($this->queues[$key]);
                continue;
            }

            if (! str_contains($key, ':')) {
                continue;
            }

            [$chip] = explode(':', $key, 2);

            if ((string) $chip === (string) $device) {
                unset($this->queues[$key]);
            }
        }
    }

    /** The queue under $key, made now when a loop is bound; null without one (then nothing can be offloaded either). */
    protected function queueAt(string $key): ?BusQueue
    {
        if (isset($this->queues[$key])) {
            return $this->queues[$key];
        }

        $loop = $this->eventLoop();

        return is_null($loop) ? null : $this->queues[$key] = new BusQueue($loop);
    }

    /** Sends $gig to the named work target, or to the configured one. A worker's exception comes back as the one it was. */
    protected function runGig(ShouldPool $gig, ?string $target): Promise
    {
        $targets = $this->target_resolver ?? throw ($this->protocolException())::noWorkTargets();

        /** @var WorkTarget $work */
        $work = $targets($target);

        // a queue target resolves with the queued job, so the promise could never mirror the blocking call
        if ($work instanceof QueueTarget) {
            throw ($this->protocolException())::offloadTargetDiscardsResult($target ?? 'default');
        }

        return $work->run($gig)->error(fn (Throwable $e) => throw GPIOLevelException::localize($e));
    }

    protected function eventLoop(): ?Loop
    {
        return is_null($this->loop_resolver) ? null : ($this->loop_resolver)();
    }
}
