<?php

namespace Fabricate\Log;

use Closure;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\NutsAndBolts\Contracts\Arrayable;
use Fabricate\NutsAndBolts\Contracts\Jsonable;
use Fabricate\Log\Events\MessageLogged;
use Fabricate\NutsAndBolts\Concerns\Conditionable;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Logger implements LoggerInterface
{
    use Conditionable;

    /**
     * The underlying logger implementation.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * The event dispatcher instance.
     *
     * @var \Fabricate\Contracts\Events\Dispatcher|null
     */
    protected ?Dispatcher $dispatcher;

    /**
     * Any context to be added to logs.
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Create a new log writer instance.
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     * @param  \Fabricate\Contracts\Events\Dispatcher|null  $dispatcher
     */
    public function __construct(LoggerInterface $logger, ?Dispatcher $dispatcher = null)
    {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    public function emergency($message, array $context = []): void
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->writeLog($level, $message, $context);
    }

    public function write($level, $message, array $context = []): void
    {
        $this->writeLog($level, $message, $context);
    }

    /**
     * Write a message to the log.
     *
     * @param string $level
     * @param  mixed  $message
     * @param array $context
     * @return void
     */
    protected function writeLog(string $level, mixed $message, array $context): void
    {
        if (method_exists($this->logger, 'isHandling') && ! $this->logger->isHandling($level)) {
            return;
        }

        $this->logger->{$level}(
            $message = $this->formatMessage($message),
            $context = array_merge($this->context, $context)
        );

        $this->fireLogEvent($level, $message, $context);
    }

    public function withContext(array $context = []): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    public function withoutContext(?array $keys = null): static
    {
        if (is_array($keys)) {
            $this->context = array_diff_key($this->context, array_flip($keys));
        } else {
            $this->context = [];
        }

        return $this;
    }

    public function listen(Closure $callback): void
    {
        if (! isset($this->dispatcher)) {
            throw new RuntimeException('Events dispatcher has not been set.');
        }

        $this->dispatcher->listen(MessageLogged::class, $callback);
    }

    protected function fireLogEvent($level, $message, array $context = []): void
    {
        if ($this->logger instanceof LogManager &&
            $this->logger->getEventDispatcher() !== null) {
            return;
        }

        $this->dispatcher?->dispatch(new MessageLogged($level, $message, $context));
    }

    protected function formatMessage($message)
    {
        return match (true) {
            is_array($message) => var_export($message, true),
            $message instanceof Jsonable => $message->toJson(),
            $message instanceof Arrayable => var_export($message->toArray(), true),
            default => (string) $message,
        };
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getEventDispatcher(): ?Dispatcher
    {
        return $this->dispatcher;
    }

    public function setEventDispatcher(Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function __call($method, $parameters)
    {
        return $this->logger->{$method}(...$parameters);
    }
}
