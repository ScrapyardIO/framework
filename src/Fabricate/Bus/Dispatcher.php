<?php

namespace Fabricate\Bus;

use Closure;
use Fabricate\Contracts\Bus\QueueingDispatcher;
use Fabricate\Contracts\Chassis\WireframeServiceContainer;
use Fabricate\Contracts\Queue\Factory as QueueFactory;
use Fabricate\Contracts\Queue\Queue as QueueContract;
use Fabricate\NutsAndBolts\Collection;
use RuntimeException;

class Dispatcher implements QueueingDispatcher
{
    protected WireframeServiceContainer $container;

    protected ?Closure $queueResolver;

    /**
     * @var array<int, callable|string>
     */
    protected array $pipes = [];

    /**
     * @var array<string, string>
     */
    protected array $handlers = [];

    protected bool $allowsDispatchingAfterResponses = true;

    public function __construct(WireframeServiceContainer $container, ?Closure $queueResolver = null)
    {
        $this->container = $container;
        $this->queueResolver = $queueResolver;
    }

    public function dispatch(mixed $command): mixed
    {
        return $this->commandShouldBeQueued($command)
            ? $this->dispatchToQueue($command)
            : $this->dispatchNow($command);
    }

    public function dispatchSync(mixed $command, mixed $handler = null): mixed
    {
        return $this->dispatchNow($command, $handler);
    }

    public function dispatchNow(mixed $command, mixed $handler = null): mixed
    {
        $callback = $this->resolveHandlerCallback($command, $handler);

        return $this->runPipes($command, $callback);
    }

    public function dispatchAfterResponse(mixed $command, mixed $handler = null): void
    {
        if (! $this->allowsDispatchingAfterResponses || ! method_exists($this->container, 'terminating')) {
            $this->dispatchSync($command, $handler);
            return;
        }

        $this->container->terminating(function () use ($command, $handler) {
            $this->dispatchSync($command, $handler);
        });
    }

    public function chain(array|Collection|null $jobs = null): mixed
    {
        $jobs = Collection::wrap($jobs);

        return $jobs->map(fn ($job) => $this->dispatch($job));
    }

    public function hasCommandHandler(mixed $command): bool
    {
        return is_object($command) && array_key_exists(get_class($command), $this->handlers);
    }

    public function getCommandHandler(mixed $command): mixed
    {
        if (! $this->hasCommandHandler($command)) {
            return false;
        }

        return $this->container->make($this->handlers[get_class($command)]);
    }

    public function pipeThrough(array $pipes): static
    {
        $this->pipes = $pipes;

        return $this;
    }

    public function map(array $map): static
    {
        $this->handlers = array_merge($this->handlers, $map);

        return $this;
    }

    public function findBatch(string $batchId): mixed
    {
        // Batching will be enabled when queue persistence drivers land.
        return null;
    }

    public function batch(array|Collection $jobs): mixed
    {
        // Batching will be enabled when queue persistence drivers land.
        return Collection::wrap($jobs)->map(fn ($job) => $this->dispatch($job));
    }

    public function dispatchToQueue(mixed $command): mixed
    {
        $queue = $this->resolveQueue();

        if (method_exists($command, 'queue')) {
            return $command->queue($queue, $command);
        }

        if (property_exists($command, 'delay') && isset($command->delay)) {
            return $queue->later($command->delay, $command);
        }

        return $queue->push($command);
    }

    protected function commandShouldBeQueued(mixed $command): bool
    {
        return is_object($command) && property_exists($command, 'shouldQueue') && (bool) $command->shouldQueue;
    }

    protected function resolveQueue(): QueueContract
    {
        if ($this->queueResolver) {
            $queue = ($this->queueResolver)(null);
        } else {
            $queueFactory = $this->container->make(QueueFactory::class);
            $queue = $queueFactory->connection();
        }

        if (! $queue instanceof QueueContract) {
            throw new RuntimeException('Queue resolver did not return a Queue implementation.');
        }

        return $queue;
    }

    protected function resolveHandlerCallback(mixed $command, mixed $handler = null): callable
    {
        if ($handler || ($handler = $this->getCommandHandler($command))) {
            return function (mixed $command) use ($handler): mixed {
                $method = method_exists($handler, 'handle') ? 'handle' : '__invoke';
                return $handler->{$method}($command);
            };
        }

        return function (mixed $command): mixed {
            $method = method_exists($command, 'handle') ? 'handle' : '__invoke';
            return $this->container->call([$command, $method]);
        };
    }

    protected function runPipes(mixed $command, callable $destination): mixed
    {
        $pipeline = array_reverse($this->pipes);

        $carry = $destination;

        foreach ($pipeline as $pipe) {
            $next = $carry;

            $carry = function (mixed $passable) use ($pipe, $next): mixed {
                if (is_callable($pipe)) {
                    return $pipe($passable, $next);
                }

                $instance = is_string($pipe) ? $this->container->make($pipe) : $pipe;

                if (! is_object($instance)) {
                    return $next($passable);
                }

                $method = method_exists($instance, 'handle') ? 'handle' : '__invoke';

                return $instance->{$method}($passable, $next);
            };
        }

        return $carry($command);
    }
}
