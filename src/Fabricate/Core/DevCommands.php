<?php

namespace Fabricate\Core;

use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Core\Enums\DevCommandColor;
use ReflectionClass;
use ReflectionException;

class DevCommands
{
    /**
     * Counter to keep track of how many colors have been assigned,.
     *
     * Used to ensure colors are reused only after all have been used at least once.
     *
     * @var int
     */
    protected static int $colorCount = 0;

    /**
     * The registered development commands.
     *
     * @var array
     */
    protected static array $commands = [];

    /**
     * The names of commands that should be included when running the "dev" command.
     *
     * @var array<int, string>
     */
    protected static array $only = [];

    /**
     * The names of commands that should be excluded when running the "dev" command.
     *
     * @var array<int, string>
     */
    protected static array $except = [];

    /**
     * Register the default development commands.
     *
     * @return void
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
     */
    public static function registerDefaults(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        self::workshop('queue:listen --tries=1 --timeout=0', 'queue');
    }

    /**
     * Register a development command.
     *
     * @param  string  $command
     * @param  string|null  $name
     * @return DevCommand
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
     */
    public static function register(string $command, ?string $name = null): DevCommand
    {
        if (! app()->runningInConsole()) {
            return new DevCommand('', [], '');
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $source = self::resolveSource($trace);
        $priority = self::resolvePriority($trace);

        $devCommand = new DevCommand($command, $source, $name, $priority);

        $existing = self::$commands[$devCommand->name()] ?? null;

        if (! $existing || $devCommand->priority() >= $existing->priority()) {
            self::$commands[$devCommand->name()] = $devCommand;
        }

        return $devCommand;
    }

    /**
     * Registers an Artisan command, automatically prefixing it with "php artisan".
     *
     * @param  string  $command
     * @param  string|null  $name
     * @return DevCommand
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
     */
    public static function workshop(string $command, ?string $name = null): DevCommand
    {
        return self::register("php artisan {$command}", $name ?? DevCommand::nameFromCommand($command));
    }

    /**
     * Get the registered development commands.
     *
     * @return array{'name': string, 'command': string, 'source': array{'file': string, 'line': int, 'class'?: string, 'function'?: string}, 'color': string}[]
     */
    public static function commands(): array
    {
        $commands = [];

        foreach (self::$commands as $command) {
            $cmd = $command->toArray();

            if ((! empty(self::$only) && ! in_array($cmd['name'], self::$only)) || in_array($cmd['name'], self::$except)) {
                continue;
            }

            $commands[] = $cmd;
        }

        return self::fillInEmptyColors($commands);
    }

    /**
     * Fill in any empty colors in the given commands array, ensuring each command has a color assigned.
     *
     * @param  array  $commands
     * @return array
     */
    protected static function fillInEmptyColors(array $commands): array
    {
        foreach ($commands as &$command) {
            if (empty($command['color'])) {
                $command['color'] = self::getColor($commands);
            }
        }

        return $commands;
    }

    /**
     * Get a color for a command, ensuring that colors are reused only after all available colors have been used at least once.
     *
     * @param  array  $commands
     * @return string
     */
    protected static function getColor(array $commands): string
    {
        $available = array_values(array_diff(
            $colors = array_map(fn ($color) => $color->value, DevCommandColor::cases()),
            $existing = array_values(array_filter(array_column($commands, 'color')))
        ));

        return $available[0] ?? $colors[self::$colorCount++ % count($colors)];
    }

    /**
     * Resolve the first external caller frame from a debug backtrace.
     *
     * @param  array<int, array{'file': string, 'line': int, 'class'?: string, 'function'?: string}>  $trace
     * @return array{'file': string, 'line': int, 'class'?: string, 'function'?: string}
     */
    protected static function resolveSource(array $trace): array
    {
        foreach ($trace as $frame) {
            if (($frame['file'] ?? null) === __FILE__) {
                continue;
            }

            if (($frame['class'] ?? null) === self::class) {
                continue;
            }

            return $frame;
        }

        return [];
    }

    /**
     * Determine the registration priority from a debug backtrace.
     *
     * @param  array<int, array{'file': string, 'line': int, 'class'?: string, 'function'?: string}>  $trace
     * @return int
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
     */
    protected static function resolvePriority(array $trace): int
    {
        foreach ($trace as $frame) {
            $file = $frame['file'] ?? null;
            $class = $frame['class'] ?? null;

            if ($file === __FILE__) {
                continue;
            }

            if ($class === self::class && ($frame['function'] ?? null) === 'registerDefaults') {
                return DevCommand::PRIORITY_DEFAULT;
            }

            if (! $file && $class) {
                $file = new ReflectionClass($class)->getFileName();
            }

            if (! $file || $file === base_path('artisan')) {
                continue;
            }

            if (! str_contains($file, base_path('vendor'))) {
                return DevCommand::PRIORITY_USERLAND;
            }
        }

        return DevCommand::PRIORITY_VENDOR;
    }

    /**
     * Set the commands that should be included when running the "dev" command.
     *
     * @param  string  ...$names
     * @return void
     */
    public static function only(...$names): void
    {
        self::$only = $names;
    }

    /**
     * Set the commands that should be excluded when running the "dev" command.
     *
     * @param  string  ...$names
     * @return void
     */
    public static function except(...$names): void
    {
        self::$except = $names;
    }
}