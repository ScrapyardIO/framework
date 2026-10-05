<?php

namespace GeneralPurposeIO\Core;

use Composer\InstalledVersions;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Voyager\Process\Factory;

/**
 * The machine the scrapyard:ext and scrapyard:modules commands run on, and the PHP binary running them. Everything
 * the two interviews learn about the machine, or do to it, goes through here.
 */
class Host
{
    public function __construct(
        protected Factory $process = new Factory,
    ) {}

    public function phpBinary(): string
    {
        return PHP_BINARY;
    }

    /** A PHP_OS_FAMILY value. */
    public function osFamily(): string
    {
        return PHP_OS_FAMILY;
    }

    /** The version of the extension the running PHP binary loads, or null when it does not load it. */
    public function extensionVersion(string $extension): ?string
    {
        $version = phpversion($extension);

        return is_string($version) ? $version : null;
    }

    /** Whether Composer installed the package into the running application. */
    public function installed(string $package): bool
    {
        return class_exists(InstalledVersions::class) && InstalledVersions::isInstalled($package);
    }

    public function home(): ?string
    {
        $home = getenv('HOME');

        return is_string($home) && $home !== '' ? $home : null;
    }

    public function exists(string $path): bool
    {
        return is_file($path);
    }

    /**
     * @param  list<string>  $extra_directories  Searched after PATH.
     */
    public function find(string $name, array $extra_directories = []): ?string
    {
        return (new ExecutableFinder)->find($name, null, $extra_directories);
    }

    /** Fetches a URL into an executable file, creating its directory. */
    public function download(string $url, string $path): bool
    {
        $filesystem = new Filesystem;

        try {
            $filesystem->mkdir(dirname($path));
            $filesystem->copy($url, $path, true);
            $filesystem->chmod($path, 0755);
        } catch (IOExceptionInterface) {
            $filesystem->remove($path);

            return false;
        }

        return true;
    }

    public function remove(string $path): void
    {
        (new Filesystem)->remove($path);
    }

    /**
     * Runs a command to its end, with no time limit.
     *
     * @param  list<string>  $command
     * @param  ?callable(string, string): void  $output  Given what the command prints, unless it has the terminal.
     * @param  bool  $terminal  Hand the command this terminal, when there is one, so it can ask questions.
     * @return array{int, string} Exit code and trimmed standard output.
     */
    public function run(array $command, ?callable $output = null, bool $terminal = false): array
    {
        $pending = $this->process->newPendingProcess()->forever();

        if ($terminal && $pending->supportsTty() && defined('STDIN') && stream_isatty(STDIN)) {
            $pending->tty();
        }

        $result = $pending->run($command, $output);

        return [$result->exitCode() ?? 1, trim($result->output())];
    }
}
