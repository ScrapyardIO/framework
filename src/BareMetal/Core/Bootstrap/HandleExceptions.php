<?php

namespace BareMetal\Core\Bootstrap;

use BareMetal\Contracts\Core\Machine;
use BareMetal\Contracts\Debug\ExceptionHandler;
use ErrorException;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\ErrorHandler;
use PHPUnit\Runner\Version;
use ScrapyardIO\NutsAndBolts\Env;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

class HandleExceptions
{
    /**
     * Reserved memory so that errors can be displayed properly on memory exhaustion.
     */
    public static ?string $reserved_memory = null;

    /**
     * The application instance.
     */
    protected static ?Machine $app = null;

    /**
     * Bootstrap the given application.
     */
    public function bootstrap(Machine $app): void
    {
        static::$reserved_memory = str_repeat('x', 32768);

        static::$app = $app;

        error_reporting(-1);

        set_error_handler($this->forwardsTo('handleError'));

        set_exception_handler($this->forwardsTo('handleException'));

        register_shutdown_function($this->forwardsTo('handleShutdown'));

        if (! $app->environment('testing')) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Report PHP deprecations, or convert PHP errors to ErrorException instances.
     *
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): void
    {
        if ($this->isDeprecation($level)) {
            $this->handleDeprecationError($message, $file, $line, $level);
        } elseif (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Reports a deprecation to the logger when logging is available.
     */
    public function handleDeprecationError(string $message, string $file, int $line, int $level = E_DEPRECATED): void
    {
        if ($this->shouldIgnoreDeprecationErrors()) {
            return;
        }

        if (! static::$app->bound('config') || ! static::$app->bound('log')) {
            return;
        }

        try {
            $logger = static::$app->make('log');
        } catch (Exception) {
            return;
        }

        if (! is_object($logger) || ! method_exists($logger, 'channel')) {
            return;
        }

        $options = static::$app['config']->get('logging.deprecations') ?? [];

        $log = $logger->channel('deprecations');

        if ($options['trace'] ?? false) {
            $log->warning((string) new ErrorException($message, 0, $level, $file, $line));
        } else {
            $log->warning(sprintf('%s in %s on line %s',
                $message, $file, $line
            ));
        }
    }

    /**
     * Determine if deprecation errors should be ignored.
     */
    protected function shouldIgnoreDeprecationErrors(): bool
    {
        return is_null(static::$app)
            || ! static::$app->hasBeenBootstrapped()
            || ! static::$app->bound('log')
            || (static::$app->runningUnitTests() && ! Env::get('LOG_DEPRECATIONS_WHILE_TESTING'));
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the Console kernel. Fatal error exceptions must be handled here.
     */
    public function handleException(Throwable $e): void
    {
        static::$reserved_memory = null;

        try {
            $this->getExceptionHandler()->report($e);
        } catch (Exception) {
            $exception_handler_failed = true;
        }

        if (static::$app->runningInConsole()) {
            $this->renderForConsole($e);

            if ($exception_handler_failed ?? false) {
                exit(1);
            }
        } else {
            $this->renderHttpResponse($e);
        }
    }

    /**
     * Render an exception to the console.
     */
    protected function renderForConsole(Throwable $e): void
    {
        $this->getExceptionHandler()->renderForConsole(new ConsoleOutput, $e);
    }

    /**
     * Render an exception as an HTTP response and send it.
     *
     * HTTP rendering is not wired yet; dump to STDERR as a safe fallback.
     */
    protected function renderHttpResponse(Throwable $e): void
    {
        if (static::$app->bound('request') && method_exists($this->getExceptionHandler(), 'render')) {
            $this->getExceptionHandler()->render(static::$app['request'], $e)->send();

            return;
        }

        fwrite(STDERR, $e::class.': '.$e->getMessage().PHP_EOL);
    }

    /**
     * Handle the PHP shutdown event.
     */
    public function handleShutdown(): void
    {
        static::$reserved_memory = null;

        if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalErrorFromPhpError($error));
        }
    }

    /**
     * Create an exception instance from a PHP fatal error array.
     *
     * Uses ErrorException until symfony/error-handler FatalError is a dependency.
     */
    protected function fatalErrorFromPhpError(array $error): ErrorException
    {
        return new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line'],
        );
    }

    /**
     * Forward a method call to the given method if an application instance exists.
     */
    protected function forwardsTo(string $method): callable
    {
        return fn (...$arguments) => static::$app
            ? $this->{$method}(...$arguments)
            : false;
    }

    /**
     * Determine if the error level is a deprecation.
     */
    protected function isDeprecation(int $level): bool
    {
        return in_array($level, [E_DEPRECATED, E_USER_DEPRECATED], true);
    }

    /**
     * Determine if the error type is fatal.
     */
    protected function isFatal(int $type): bool
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE], true);
    }

    /**
     * Get an instance of the exception handler.
     */
    protected function getExceptionHandler(): ExceptionHandler
    {
        return static::$app->make(ExceptionHandler::class);
    }

    /**
     * Clear the local application instance from memory.
     */
    public static function forgetApp(): void
    {
        static::$app = null;
    }

    /**
     * Flush the bootstrapper's global state.
     */
    public static function flushState(?TestCase $test_case = null): void
    {
        if (is_null(static::$app)) {
            return;
        }

        static::flushHandlersState($test_case);

        static::$app = null;

        static::$reserved_memory = null;
    }

    /**
     * Flush the bootstrapper's global handlers state.
     */
    public static function flushHandlersState(?TestCase $test_case = null): void
    {
        while (get_exception_handler() !== null) {
            restore_exception_handler();
        }

        while (get_error_handler() !== null) {
            restore_error_handler();
        }

        if (class_exists(ErrorHandler::class)) {
            $instance = ErrorHandler::instance();

            if ((fn () => $this->enabled ?? false)->call($instance)) {
                $instance->disable();

                if (class_exists(Version::class) && version_compare(Version::id(), '12.3.4', '>=')) {
                    $instance->enable($test_case);
                } else {
                    $instance->enable();
                }
            }
        }
    }
}
