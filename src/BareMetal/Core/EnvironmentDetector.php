<?php

namespace BareMetal\Core;

use Closure;

class EnvironmentDetector
{
    /**
     * Detect the application's current environment.
     */
    public function detect(Closure $callback, ?array $console_args = null): string
    {
        if ($console_args) {
            return $this->detectConsoleEnvironment($callback, $console_args);
        }

        return $this->detectWebEnvironment($callback);
    }

    /**
     * Set the application environment for a web request.
     */
    protected function detectWebEnvironment(Closure $callback): string
    {
        return $callback();
    }

    /**
     * Set the application environment from command-line arguments.
     */
    protected function detectConsoleEnvironment(Closure $callback, array $args): string
    {
        if (! is_null($value = $this->getEnvironmentArgument($args))) {
            return $value;
        }

        return $this->detectWebEnvironment($callback);
    }

    /**
     * Get the environment argument from the console.
     */
    protected function getEnvironmentArgument(array $args): ?string
    {
        foreach ($args as $i => $value) {
            if ($value === '--env') {
                return $args[$i + 1] ?? null;
            }

            if (str_starts_with($value, '--env=')) {
                return explode('=', $value, 2)[1] ?? null;
            }
        }

        return null;
    }
}
