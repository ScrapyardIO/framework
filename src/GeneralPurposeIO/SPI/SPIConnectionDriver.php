<?php

namespace GeneralPurposeIO\SPI;

use Fiber;
use Throwable;
use Voyager\NutsAndBolts\Collection;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;
use GeneralPurposeIO\NutsAndBolts\BusQueue;
use GeneralPurposeIO\NutsAndBolts\OffloadsBusJobs;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;

/**
 * One queue of offloaded jobs per bus: a job may hold chip select across messages, so two on one bus never overlap.
 * A select() outside that queue (the main stack, an app's own fiber) pauses it and waits for the running job, so the
 * bus is that slave's alone from chip select down to chip select up.
 */
abstract class SPIConnectionDriver
{
    use OffloadsBusJobs;

    public readonly Collection $connections;

    /** @var array<string, SPITransport> "<device>:<chip select>" => transport */
    protected array $transports = [];

    /** @var array<string, SPIBusSettings> "<device>" => what its factory registered it with, for workers to connect with */
    private array $settings = [];

    /** @var array<string, array{SPITransport, ?Fiber, ?BusQueue}> "<device>" => the select() holding the bus: its slave, the fiber it runs in (null: the main stack), the queue it paused */
    private array $held = [];

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function getTransport(int|string $device, int $chip_select): SPITransport;

    abstract protected function newConnection(int|string $device): SPIConnectionFactory;

    /** Close what connectTo() opened for one bus. Its slaves are already closed. */
    abstract protected function closeConnection(mixed $handle): void;

    public function register(string|int $name, mixed $handle): static
    {
        $this->connections->put($name, $handle);

        return $this;
    }

    public function connectTo(int|string $device): SPIConnectionFactory
    {
        if ($this->connections->has($device)) {
            throw SPIException::alreadyConnected($device);
        }

        return $this->newConnection($device);
    }

    /** Wire-internal: the factory records the settings it registered a bus with. */
    public function configuredWith(string|int $device, SPIBusSettings $settings): static
    {
        $this->settings[(string) $device] = $settings;

        return $this;
    }

    /** What a bus was registered with through its factory; null when it is not connected, or was registered by hand. */
    public function settingsOf(string|int $device): ?SPIBusSettings
    {
        return $this->settings[(string) $device] ?? null;
    }

    /** One transport per chip select per bus; a closed one is replaced by a fresh one. */
    public function device(string|int $device, int $chip_select = 0): ?SPITransport
    {
        if (! $this->connections->has($device)) {
            return null;
        }

        $key = "{$device}:{$chip_select}";
        $transport = $this->transports[$key] ?? null;

        if (is_null($transport) || $transport->closed()) {
            $transport = $this->transports[$key] = $this->getTransport($device, $chip_select)->attachTo($this, $device);
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

        $this->forgetQueues($device);
        unset($this->held[(string) $device], $this->settings[(string) $device]);

        if ($this->connections->has($device)) {
            $this->closeConnection($this->connections->get($device));
            $this->connections->forget($device);
        }
    }

    /**
     * Wire-internal: a slave's select() takes the bus. It waits out a select() running elsewhere and, unless it runs
     * inside the bus's running job, keeps queued jobs from starting and waits for the running one to settle.
     * @throws SPIException another slave's select() holds the bus on this very stack
     */
    public function hold(string|int $device, SPITransport $slave): void
    {
        $queue = $this->queueAt($this->queueKey($device, $slave->chipSelect()));
        $paused = ! is_null($queue) && ! $queue->insideRunningJob() ? $queue : null;
        $paused?->pause();

        try {
            do {
                $this->ensureFree($device, $slave);
                $paused?->awaitRunning();
            } while (isset($this->held[(string) $device]));        // taken by a select() elsewhere while this one waited
        } catch (Throwable $e) {
            $paused?->resume();

            throw $e;
        }

        $this->held[(string) $device] = [$slave, Fiber::getCurrent(), $paused];
    }

    /** Wire-internal: the select() gives the bus back, and the queue it paused carries on. */
    public function letGo(string|int $device, SPITransport $slave): void
    {
        $holder = $this->held[(string) $device] ?? null;

        if (is_null($holder) || $holder[0] !== $slave || $holder[1] !== Fiber::getCurrent()) {
            return;
        }

        unset($this->held[(string) $device]);
        $holder[2]?->resume();
    }

    /**
     * Wire-internal: returns once $slave may use the bus. Another slave's select() on this very stack throws; a
     * select() running elsewhere (another fiber, or the main stack while this is a fiber) is waited out.
     * @throws SPIException
     */
    public function ensureFree(string|int $device, SPITransport $slave): void
    {
        while (! is_null($holder = $this->held[(string) $device] ?? null)) {
            if ($holder[1] === Fiber::getCurrent()) {
                if ($holder[0] === $slave) {
                    return;                 // inside its own select()
                }

                throw SPIException::busHeld($device, $holder[0]->chipSelect());
            }

            $loop = $this->eventLoop() ?? throw SPIException::busHeld($device, $holder[0]->chipSelect());
            $loop->until(fn (): bool => ($this->held[(string) $device] ?? null) !== $holder);
        }
    }

    /** Every slave on a bus shares one queue. */
    protected function queueKey(string|int $device, int $chip_select): string
    {
        return (string) $device;
    }

    /**
     * An SPIBusGig to the named worker pool, or to the default one, carrying the bus settings and the slave's clock
     * so a worker opens the bus the way this process did.
     */
    protected function dispatch(string|int $device, int $chip_select, BusJob $job, ?string $pool, Loop $loop, BusQueue $queue): Promise
    {
        $settings = $this->settings[(string) $device] ?? throw SPIException::busSettingsUnknown($device);
        $clock = ($this->transports["{$device}:{$chip_select}"] ?? null)?->clock();

        return $this->runGig(new SPIBusGig(static::class, $device, $chip_select, $settings, $clock, $job), $pool);
    }

    protected function protocolException(): string
    {
        return SPIException::class;
    }

    protected function closedReason(int $chip_select): Throwable
    {
        return SPIException::transportClosed($chip_select);
    }
}
