<?php

namespace Fabricate\Core;

class AliasLoader
{
    /**
     * The array of class aliases.
     *
     * @var array
     */
    protected array $aliases;

    /**
     * Indicates if a loader has been registered.
     *
     * @var bool
     */
    protected bool $registered = false;

    /**
     * The namespace for all real-time magic aliases.
     *
     * @var string
     */
    protected static string $magicAliasNamespace = 'MagicAliases\\';

    /**
     * The singleton instance of the loader.
     *
     * @var AliasLoader|null
     */
    protected static ?AliasLoader $instance = null;

    /**
     * Create a new AliasLoader instance.
     *
     * @param array $aliases
     */
    private function __construct(array $aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Get or create the singleton alias loader instance.
     *
     * @param  array  $aliases
     * @return AliasLoader
     */
    public static function getInstance(array $aliases = []): AliasLoader
    {
        if (is_null(static::$instance)) {
            return static::$instance = new static($aliases);
        }

        $aliases = array_merge(static::$instance->getAliases(), $aliases);

        static::$instance->setAliases($aliases);

        return static::$instance;
    }

    /**
     * Load a class alias if it is registered.
     *
     * @param string $alias
     * @return bool|null
     */
    public function load(string $alias): ?bool
    {
        if (static::$magicAliasNamespace && str_starts_with($alias, static::$magicAliasNamespace)) {
            $this->loadMagicAlias($alias);

            return true;
        }

        if (isset($this->aliases[$alias])) {
            return class_alias($this->aliases[$alias], $alias);
        }

        return null;
    }

    /**
     * Load a real-time magic alias for the given class name.
     *
     * @param string $alias
     * @return void
     */
    protected function loadMagicAlias(string $alias): void
    {
        require $this->ensureMagicAliasExists($alias);
    }

    /**
     * Ensure that the given alias has an existing real-time magic alias class.
     *
     * @param string $alias
     * @return string
     */
    protected function ensureMagicAliasExists(string $alias): string
    {
        if (is_file($path = storage_path('framework/cache/magic-alias-'.sha1($alias).'.php'))) {
            return $path;
        }

        $stub = $this->formatMagicAliasStub(
            $alias, file_get_contents(__DIR__.'/stubs/magic-alias.stub')
        );

        // Atomic write to prevent race conditions...
        $tempPath = tempnam(dirname($path), 'magic-alias-');

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        @chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $stub);

        rename($tempPath, $path);

        return $path;
    }

    /**
     * Format the magic alias stub with the proper namespace and class.
     *
     * @param string $alias
     * @param  string  $stub
     * @return string
     */
    protected function formatMagicAliasStub(string $alias, string $stub): string
    {
        $replacements = [
            str_replace('/', '\\', dirname(str_replace('\\', '/', $alias))),
            class_basename($alias),
            substr($alias, strlen(static::$magicAliasNamespace)),
        ];

        return str_replace(
            ['DummyNamespace', 'DummyClass', 'DummyTarget'], $replacements, $stub
        );
    }

    /**
     * Add an alias to the loader.
     *
     * @param string $alias
     * @param string $class
     * @return void
     */
    public function alias(string $alias, string $class): void
    {
        $this->aliases[$alias] = $class;
    }

    /**
     * Register the loader on the autoloader stack.
     *
     * @return void
     */
    public function register(): void
    {
        if (! $this->registered) {
            $this->prependToLoaderStack();

            $this->registered = true;
        }
    }

    /**
     * Prepend the load method to the autoloader stack.
     *
     * @return void
     */
    protected function prependToLoaderStack(): void
    {
        spl_autoload_register($this->load(...), true, true);
    }

    /**
     * Get the registered aliases.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Set the registered aliases.
     *
     * @param  array  $aliases
     * @return void
     */
    public function setAliases(array $aliases): void
    {
        $this->aliases = $aliases;
    }

    /**
     * Indicates if the loader has been registered.
     *
     * @return bool
     */
    public function isRegistered(): bool
    {
        return $this->registered;
    }

    /**
     * Set the "registered" state of the loader.
     *
     * @param  bool  $value
     * @return void
     */
    public function setRegistered(bool $value): void
    {
        $this->registered = $value;
    }

    /**
     * Set the real-time magic alias namespace.
     *
     * @param string $namespace
     * @return void
     */
    public static function setMagicAliasNamespace(string $namespace): void
    {
        static::$magicAliasNamespace = rtrim($namespace, '\\').'\\';
    }

    /**
     * Set the value of the singleton alias loader.
     *
     * @param AliasLoader $loader
     * @return void
     */
    public static function setInstance(AliasLoader $loader): void
    {
        static::$instance = $loader;
    }

    /**
     * Clone method.
     *
     * @return void
     */
    private function __clone()
    {
        //
    }
}
