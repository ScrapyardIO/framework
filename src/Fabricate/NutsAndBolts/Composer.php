<?php

namespace Fabricate\NutsAndBolts;

use Closure;
use JsonException;
use RuntimeException;
use Fabricate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\OutputInterface;

class Composer
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * The working path to regenerate from.
     *
     * @var string|null
     */
    protected ?string $workingPath;

    /**
     * Create a new Composer manager instance.
     *
     * @param Filesystem $files
     * @param string|null $workingPath
     */
    public function __construct(Filesystem $files, ?string $workingPath = null)
    {
        $this->files = $files;
        $this->workingPath = $workingPath;
    }

    /**
     * Determine if the given Composer package is installed.
     *
     * @param string $package
     * @return bool
     *
     * @throws RuntimeException
     */
    public function hasPackage(string $package): bool
    {
        $composer = json_decode(file_get_contents($this->findComposerFile()), true);

        return array_key_exists($package, $composer['require'] ?? [])
            || array_key_exists($package, $composer['require-dev'] ?? []);
    }

    /**
     * Install the given Composer packages into the application.
     *
     * @param  array<int, string>  $packages
     * @param  bool  $dev
     * @param  Closure|OutputInterface|null  $output
     * @param string|null $composerBinary
     * @return bool
     */
    public function requirePackages(array $packages, bool $dev = false, Closure|OutputInterface|null $output = null, ?string $composerBinary = null): bool
    {
        $command = new Collection([
            ...$this->findComposer($composerBinary),
            'require',
            ...$packages,
        ])
            ->when($dev, function ($command) {
                $command->push('--dev');
            })->all();

        return 0 === $this->getProcess($command, ['COMPOSER_MEMORY_LIMIT' => '-1'])
                ->run(
                    $output instanceof OutputInterface
                        ? function ($type, $line) use ($output) {
                        $output->write('    '.$line);
                    } : $output
                );
    }

    /**
     * Remove the given Composer packages from the application.
     *
     * @param  array<int, string>  $packages
     * @param  bool  $dev
     * @param  Closure|OutputInterface|null  $output
     * @param string|null $composerBinary
     * @return bool
     */
    public function removePackages(array $packages, bool $dev = false, Closure|OutputInterface|null $output = null, ?string $composerBinary = null): bool
    {
        $command = new Collection([
            ...$this->findComposer($composerBinary),
            'remove',
            ...$packages,
        ])
            ->when($dev, function ($command) {
                $command->push('--dev');
            })->all();

        return 0 === $this->getProcess($command, ['COMPOSER_MEMORY_LIMIT' => '-1'])
            ->run(
                $output instanceof OutputInterface
                    ? function ($type, $line) use ($output) {
                    $output->write('    '.$line);
                } : $output
            );
    }

    /**
     * Modify the "composer.json" file contents using the given callback.
     *
     * @param  callable(array<string, mixed>):array<string, mixed>  $callback
     * @return void
     *
     * @throws JsonException
     * @throws RuntimeException
     */
    public function modify(callable $callback): void
    {
        $composerFile = $this->findComposerFile();

        $composer = json_decode(file_get_contents($composerFile), true, 512, JSON_THROW_ON_ERROR);

        file_put_contents(
            $composerFile,
            json_encode(
                call_user_func($callback, $composer),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /**
     * Regenerate the Composer autoloader files.
     *
     * @param string|array<string> $extra
     * @param string|null $composerBinary
     * @return int
     */
    public function dumpAutoloads(array|string $extra = '', ?string $composerBinary = null): int
    {
        $extra = $extra ? (array) $extra : [];

        $command = array_merge($this->findComposer($composerBinary), ['dump-autoload'], $extra);

        return $this->getProcess($command)->run();
    }

    /**
     * Regenerate the optimized Composer autoloader files.
     *
     * @param string|null $composerBinary
     * @return int
     */
    public function dumpOptimized(?string $composerBinary = null): int
    {
        return $this->dumpAutoloads('--optimize', $composerBinary);
    }

    /**
     * Get the Composer binary / command for the environment.
     *
     * @param string|null $composerBinary
     * @return array<string>
     */
    public function findComposer(?string $composerBinary = null): array
    {
        if (! is_null($composerBinary) && $this->files->exists($composerBinary)) {
            return [$this->phpBinary(), $composerBinary];
        } elseif ($this->files->exists($this->workingPath.'/composer.phar')) {
            return [$this->phpBinary(), 'composer.phar'];
        }

        return ['composer'];
    }

    /**
     * Get the path to the "composer.json" file.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function findComposerFile(): string
    {
        $composerFile = "{$this->workingPath}/composer.json";

        if (! file_exists($composerFile)) {
            throw new RuntimeException("Unable to locate `composer.json` file at [{$this->workingPath}].");
        }

        return $composerFile;
    }

    /**
     * Get the PHP binary.
     *
     * @return string
     */
    protected function phpBinary(): string
    {
        return php_binary();
    }

    /**
     * Get a new Symfony process instance.
     *
     * @param  array<string>  $command
     * @param  array<string, string>  $env
     * @return Process
     */
    protected function getProcess(array $command, array $env = []): Process
    {
        return new Process($command, $this->workingPath, $env)->setTimeout(null);
    }

    /**
     * Set the working path used by the class.
     *
     * @param string $path
     * @return $this
     */
    public function setWorkingPath(string $path): static
    {
        $this->workingPath = realpath($path);

        return $this;
    }

    /**
     * Get the version of Composer.
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        $command = array_merge($this->findComposer(), ['-V', '--no-ansi']);

        $process = $this->getProcess($command);

        $process->run();

        $output = $process->getOutput();

        if (preg_match('/(\d+(\.\d+){2})/', $output, $version)) {
            return $version[1];
        }

        return explode(' ', $output)[2] ?? null;
    }
}
