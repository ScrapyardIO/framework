<?php

namespace ScrapyardIO\NutsAndBolts;

use Closure;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use BareMetal\Filesystem\Filesystem;
use PhpOption\Option;
use PhpOption\Some;
use RuntimeException;

class Env
{
    /**
     * Indicates if the putenv adapter is enabled.
     *
     * @var bool
     */
    protected static $putenv = true;

    /**
     * The environment repository instance.
     *
     * @var \Dotenv\Repository\RepositoryInterface|null
     */
    protected static $repository;

    /**
     * The list of custom adapters for loading environment variables.
     *
     * @var array<Closure>
     */
    protected static $custom_adapters = [];

    /**
     * Enable the putenv adapter.
     */
    public static function enablePutenv(): void
    {
        static::$putenv = true;
        static::$repository = null;
    }

    /**
     * Disable the putenv adapter.
     */
    public static function disablePutenv(): void
    {
        static::$putenv = false;
        static::$repository = null;
    }

    /**
     * Register a custom adapter creator Closure.
     */
    public static function extend(Closure $callback, ?string $name = null): void
    {
        if (! is_null($name)) {
            static::$custom_adapters[$name] = $callback;
        } else {
            static::$custom_adapters[] = $callback;
        }

        static::$repository = null;
    }

    /**
     * Get the environment repository instance.
     */
    public static function getRepository(): RepositoryInterface
    {
        if (static::$repository === null) {
            $builder = RepositoryBuilder::createWithDefaultAdapters();

            if (static::$putenv) {
                $builder = $builder->addAdapter(PutenvAdapter::class);
            }

            foreach (static::$custom_adapters as $adapter) {
                $builder = $builder->addAdapter($adapter());
            }

            static::$repository = $builder->immutable()->make();
        }

        return static::$repository;
    }

    /**
     * Get the value of an environment variable.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getOption($key)->getOrCall(fn () => value($default));
    }

    /**
     * Get the value of a required environment variable.
     * @throws RuntimeException
     */
    public static function getOrFail($key): mixed
    {
        return self::getOption($key)->getOrThrow(new RuntimeException("Environment variable [$key] has no value."));
    }

    /**
     * Write an array of key-value pairs to the environment file.
     *
     * @throws RuntimeException
     * @throws \BareMetal\Contracts\Filesystem\FileNotFoundException
     */
    public static function writeVariables(array $variables, string $path_to_file, bool $overwrite = false): void
    {
        $filesystem = new Filesystem;

        if ($filesystem->missing($path_to_file)) {
            throw new RuntimeException("The file [{$path_to_file}] does not exist.");
        }

        $lines = explode(PHP_EOL, $filesystem->get($path_to_file));

        foreach ($variables as $key => $value) {
            $lines = self::addVariableToEnvContents($key, $value, $lines, $overwrite);
        }

        $filesystem->put($path_to_file, implode(PHP_EOL, $lines));
    }

    /**
     * Write a single key-value pair to the environment file.
     *
     * @throws RuntimeException
     * @throws \BareMetal\Contracts\Filesystem\FileNotFoundException
     */
    public static function writeVariable(string $key, mixed $value, string $path_to_file, bool $overwrite = false): void
    {
        $filesystem = new Filesystem;

        if ($filesystem->missing($path_to_file)) {
            throw new RuntimeException("The file [{$path_to_file}] does not exist.");
        }

        $envContent = $filesystem->get($path_to_file);

        $lines = explode(PHP_EOL, $envContent);
        $lines = self::addVariableToEnvContents($key, $value, $lines, $overwrite);

        $filesystem->put($path_to_file, implode(PHP_EOL, $lines));
    }

    /**
     * Add a variable to the environment file contents.
     */
    protected static function addVariableToEnvContents(string $key, mixed $value, array $envLines, bool $overwrite): array
    {
        $prefix = explode('_', $key)[0].'_';
        $lastPrefixIndex = -1;

        $shouldQuote = preg_match('/^[a-zA-Z0-9]+$/', $value) === 0;

        $lineToAddVariations = [
            $key.'='.(is_string($value) ? self::prepareQuotedValue($value) : $value),
            $key.'='.$value,
        ];

        $lineToAdd = $shouldQuote ? $lineToAddVariations[0] : $lineToAddVariations[1];

        if ($value === '') {
            $lineToAdd = $key.'=';
        }

        foreach ($envLines as $index => $line) {
            if (str_starts_with($line, $prefix)) {
                $lastPrefixIndex = $index;
            }

            if (in_array($line, $lineToAddVariations)) {
                // This exact line already exists, so we don't need to add it again.
                return $envLines;
            }

            if ($line === $key.'=') {
                // If the value is empty, we can replace it with the new value.
                $envLines[$index] = $lineToAdd;

                return $envLines;
            }

            if (str_starts_with($line, $key.'=')) {
                if (! $overwrite) {
                    return $envLines;
                }

                $envLines[$index] = $lineToAdd;

                return $envLines;
            }
        }

        if ($lastPrefixIndex === -1) {
            if (count($envLines) && $envLines[count($envLines) - 1] !== '') {
                $envLines[] = '';
            }

            return array_merge($envLines, [$lineToAdd]);
        }

        return array_merge(
            array_slice($envLines, 0, $lastPrefixIndex + 1),
            [$lineToAdd],
            array_slice($envLines, $lastPrefixIndex + 1)
        );
    }

    /**
     * Get the possible option for this environment variable.
     */
    protected static function getOption(string $key): Some|Option|null
    {
        return Option::fromValue(static::getRepository()->get($key))
            ->map(function ($value) {
                switch (strtolower($value)) {
                    case 'true':
                    case '(true)':
                        return true;
                    case 'false':
                    case '(false)':
                        return false;
                    case 'empty':
                    case '(empty)':
                        return '';
                    case 'null':
                    case '(null)':
                        return null;
                }

                if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
                    return $matches[2];
                }

                return $value;
            });
    }

    /**
     * Wrap a string in quotes, choosing double or single quotes.
     */
    protected static function prepareQuotedValue(string $input): string
    {
        return str_contains($input, '"')
            ? "'".self::addSlashesExceptFor($input, ['"'])."'"
            : '"'.self::addSlashesExceptFor($input, ["'"]).'"';
    }

    /**
     * Escape a string using addslashes, excluding the specified characters from being escaped.
     */
    protected static function addSlashesExceptFor(string $value, array $except = []): string
    {
        $escaped = addslashes($value);

        foreach ($except as $character) {
            $escaped = str_replace('\\'.$character, $character, $escaped);
        }

        return $escaped;
    }
}
