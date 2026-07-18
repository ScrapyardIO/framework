<?php

namespace Fabricate\Log;

use Closure;
use Fabricate\Contracts\Core\Machine;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Str;
use InvalidArgumentException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\ProcessorInterface;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use Stringable;
use Throwable;

use function Fabricate\NutsAndBolts\enum_value;

/**
 * @mixin \Fabricate\Log\Logger
 */
class LogManager implements LoggerInterface
{
    use ParsesLogConfiguration;

    protected Machine $app;

    /**
     * @var array<string, \Psr\Log\LoggerInterface>
     */
    protected array $channels = [];

    protected array $sharedContext = [];

    /**
     * @var array<string, \Closure>
     */
    protected array $customCreators = [];

    protected string $dateFormat = 'Y-m-d H:i:s';

    public function __construct(Machine $app)
    {
        $this->app = $app;
    }

    public function build(array $config): LoggerInterface
    {
        unset($this->channels['ondemand']);

        return $this->get('ondemand', $config);
    }

    public function stack(array $channels, $channel = null): LoggerInterface
    {
        return (new Logger(
            $this->createStackDriver(['channels' => $channels, 'name' => $channel]),
            $this->app->bound('events') ? $this->app['events'] : null
        ))->withContext($this->sharedContext);
    }

    public function channel($channel = null): LoggerInterface
    {
        return $this->driver($channel);
    }

    public function driver($driver = null): LoggerInterface
    {
        return $this->get($this->parseDriver(enum_value($driver)));
    }

    protected function get($name, ?array $config = null): LoggerInterface
    {
        try {
            return $this->channels[$name] ?? with($this->resolve($name, $config), function ($logger) use ($name) {
                return $this->channels[$name] = $this->tap(
                    $name,
                    new Logger($logger, $this->app->bound('events') ? $this->app['events'] : null)
                )->withContext($this->sharedContext);
            });
        } catch (Throwable $e) {
            return tap($this->createEmergencyLogger(), function ($logger) use ($e) {
                $logger->emergency('Unable to create configured logger. Using emergency logger.', [
                    'exception' => $e,
                ]);
            });
        }
    }

    protected function tap($name, Logger $logger): Logger
    {
        foreach ($this->configurationFor($name)['tap'] ?? [] as $tap) {
            [$class, $arguments] = $this->parseTap($tap);

            $this->app->make($class)->__invoke($logger, ...explode(',', $arguments));
        }

        return $logger;
    }

    protected function parseTap($tap): array
    {
        return str_contains($tap, ':') ? explode(':', $tap, 2) : [$tap, ''];
    }

    protected function createEmergencyLogger(): LoggerInterface
    {
        $config = $this->configurationFor('emergency') ?? [];

        $handler = new StreamHandler(
            $config['path'] ?? $this->app->storagePath('logs/scrapyard-io.log'),
            $this->level(['level' => 'debug'])
        );

        return new Logger(
            new Monolog('scrapyard-io', $this->prepareHandlers([$handler])),
            $this->app->bound('events') ? $this->app['events'] : null
        );
    }

    protected function resolve($name, ?array $config = null): LoggerInterface
    {
        $config ??= $this->configurationFor($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Log [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    protected function createCustomDriver(array $config): LoggerInterface
    {
        $factory = is_callable($via = $config['via']) ? $via : $this->app->make($via);

        return $factory($config);
    }

    protected function createStackDriver(array $config): LoggerInterface
    {
        if (is_string($config['channels'])) {
            $config['channels'] = explode(',', $config['channels']);
        }

        $handlers = (new Collection($config['channels']))
            ->flatMap(function ($channel) {
                return $channel instanceof LoggerInterface
                    ? $channel->getHandlers()
                    : $this->channel($channel)->getHandlers();
            })
            ->all();

        $processors = (new Collection($config['channels']))
            ->flatMap(function ($channel) {
                return $channel instanceof LoggerInterface
                    ? $channel->getProcessors()
                    : $this->channel($channel)->getProcessors();
            })
            ->all();

        if ($config['ignore_exceptions'] ?? false) {
            $handlers = [new WhatFailureGroupHandler($handlers)];
        }

        return new Monolog($this->parseChannel($config), $handlers, $processors);
    }

    protected function createSingleDriver(array $config): LoggerInterface
    {
        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(
                new StreamHandler(
                    $config['path'], $this->level($config),
                    $config['bubble'] ?? true, $config['permission'] ?? null, $config['locking'] ?? false
                ), $config
            ),
        ], $config['replace_placeholders'] ?? false ? [new PsrLogMessageProcessor()] : []);
    }

    protected function createDailyDriver(array $config): LoggerInterface
    {
        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(new RotatingFileHandler(
                $config['path'], $config['days'] ?? 7, $this->level($config),
                $config['bubble'] ?? true, $config['permission'] ?? null, $config['locking'] ?? false
            ), $config),
        ], $config['replace_placeholders'] ?? false ? [new PsrLogMessageProcessor()] : []);
    }

    /*
     * Hosted Slack webhook driver intentionally omitted for non-server deployments.
     *
     * protected function createSlackDriver(array $config): LoggerInterface { ... }
     */

    protected function createSyslogDriver(array $config): LoggerInterface
    {
        $appName = 'scrapyard-io';

        if ($this->app->bound('config')) {
            $appName = (string) ($this->app['config']['machine.name'] ?? $this->app['config']['app.name'] ?? $appName);
        }

        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(new SyslogHandler(
                Str::snake($appName, '-'),
                $config['facility'] ?? LOG_USER, $this->level($config)
            ), $config),
        ], $config['replace_placeholders'] ?? false ? [new PsrLogMessageProcessor()] : []);
    }

    protected function createErrorlogDriver(array $config): LoggerInterface
    {
        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(new ErrorLogHandler(
                $config['type'] ?? ErrorLogHandler::OPERATING_SYSTEM, $this->level($config)
            )),
        ], $config['replace_placeholders'] ?? false ? [new PsrLogMessageProcessor()] : []);
    }

    protected function createMonologDriver(array $config): LoggerInterface
    {
        if (! is_a($config['handler'], HandlerInterface::class, true)) {
            throw new InvalidArgumentException(
                $config['handler'].' must be an instance of '.HandlerInterface::class
            );
        }

        (new Collection($config['processors'] ?? []))->each(function ($processor) {
            $processor = $processor['processor'] ?? $processor;

            if (! is_a($processor, ProcessorInterface::class, true)) {
                throw new InvalidArgumentException(
                    $processor.' must be an instance of '.ProcessorInterface::class
                );
            }
        });

        $with = array_merge(
            ['level' => $this->level($config)],
            $config['with'] ?? [],
            $config['handler_with'] ?? []
        );

        $handler = $this->prepareHandler(
            $this->app->make($config['handler'], $with), $config
        );

        $processors = (new Collection($config['processors'] ?? []))
            ->map(fn ($processor) => $this->app->make($processor['processor'] ?? $processor, $processor['with'] ?? []))
            ->toArray();

        return new Monolog(
            $this->parseChannel($config),
            [$handler],
            $processors,
        );
    }

    protected function createNullDriver(): LoggerInterface
    {
        return new Monolog('null', [new NullHandler]);
    }

    protected function prepareHandlers(array $handlers): array
    {
        foreach ($handlers as $key => $handler) {
            $handlers[$key] = $this->prepareHandler($handler);
        }

        return $handlers;
    }

    protected function prepareHandler(HandlerInterface $handler, array $config = []): HandlerInterface
    {
        if (isset($config['action_level'])) {
            $handler = new FingersCrossedHandler(
                $handler,
                $this->actionLevel($config),
                0,
                true,
                $config['stop_buffering'] ?? true
            );
        }

        if (! $handler instanceof FormattableHandlerInterface) {
            return $handler;
        }

        if (! isset($config['formatter'])) {
            $handler->setFormatter($this->formatter());
        } elseif ($config['formatter'] !== 'default') {
            $handler->setFormatter($this->app->make($config['formatter'], $config['formatter_with'] ?? []));
        }

        return $handler;
    }

    protected function formatter()
    {
        return new LineFormatter(null, $this->dateFormat, true, true, true);
    }

    public function shareContext(array $context): static
    {
        foreach ($this->channels as $channel) {
            $channel->withContext($context);
        }

        $this->sharedContext = array_merge($this->sharedContext, $context);

        return $this;
    }

    public function sharedContext(): array
    {
        return $this->sharedContext;
    }

    public function withoutContext(?array $keys = null): static
    {
        foreach ($this->channels as $channel) {
            if (method_exists($channel, 'withoutContext')) {
                $channel->withoutContext($keys);
            }
        }

        return $this;
    }

    public function flushSharedContext(): static
    {
        $this->sharedContext = [];

        return $this;
    }

    protected function getFallbackChannelName(): string
    {
        return $this->app->bound('env') ? $this->app->environment() : 'production';
    }

    protected function configurationFor($name): ?array
    {
        if (! $this->app->bound('config')) {
            return $this->defaultChannelConfig($name);
        }

        return $this->app['config']["logging.channels.{$name}"] ?? $this->defaultChannelConfig($name);
    }

    protected function defaultChannelConfig(string $name): ?array
    {
        return match ($name) {
            'single' => [
                'driver' => 'single',
                'path' => $this->app->storagePath('logs/scrapyard-io.log'),
                'level' => 'debug',
                'replace_placeholders' => true,
            ],
            'stderr' => [
                'driver' => 'monolog',
                'level' => 'debug',
                'handler' => StreamHandler::class,
                'handler_with' => [
                    'stream' => 'php://stderr',
                ],
                'processors' => [PsrLogMessageProcessor::class],
            ],
            'null' => [
                'driver' => 'monolog',
                'handler' => NullHandler::class,
            ],
            'emergency' => [
                'path' => $this->app->storagePath('logs/scrapyard-io.log'),
            ],
            default => null,
        };
    }

    public function getDefaultDriver(): ?string
    {
        if (! $this->app->bound('config')) {
            return 'single';
        }

        return $this->app['config']['logging.default'] ?? 'single';
    }

    public function setDefaultDriver($name): void
    {
        $this->app['config']['logging.default'] = enum_value($name);
    }

    public function extend($driver, Closure $callback): static
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    public function forgetChannel($driver = null): void
    {
        $driver = $this->parseDriver($driver);

        if (isset($this->channels[$driver])) {
            unset($this->channels[$driver]);
        }
    }

    protected function parseDriver($driver): ?string
    {
        $driver ??= $this->getDefaultDriver();

        if ($this->app->runningUnitTests()) {
            $driver ??= 'null';
        }

        if (is_null($driver)) {
            return null;
        }

        return trim($driver);
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->driver()->emergency($message, $context);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->driver()->alert($message, $context);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->driver()->critical($message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->driver()->error($message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->driver()->warning($message, $context);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->driver()->notice($message, $context);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->driver()->info($message, $context);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->driver()->debug($message, $context);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->driver()->log($level, $message, $context);
    }

    public function setApplication(Machine $app): static
    {
        $this->app = $app;

        return $this;
    }

    public function __call(string $method, array $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
