<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\Core\Host;
use Voyager\Process\Factory;
use Voyager\Process\PendingProcess;

/**
 * A machine described up front: operating system, loaded extensions, installed packages, files and executables.
 * The Process factory's fake answers each command by the first rule whose text appears in it, and records it.
 */
final class DescribedHost extends Host
{
    /** @var list<list<string>> */
    public array $commands = [];

    /** @var list<array{string, string}> */
    public array $downloads = [];

    /** @var list<string> */
    public array $removed = [];

    /**
     * @param  list<string>  $loaded  Extensions loaded at 0.10.0.
     * @param  array<string, string>  $versions  Extensions loaded at the given version.
     * @param  list<string>  $installed
     * @param  list<string>  $files
     * @param  array<string, string>  $executables
     * @param  list<array{string, int, string}>  $rules  [text in the command line, exit code, stdout]; first match wins.
     */
    public function __construct(
        public string $php = '/opt/php/bin/php',
        public string $os = 'Linux',
        public array $loaded = [],
        public array $versions = [],
        public array $installed = [],
        public array $files = [],
        public ?string $home = '/home/dev',
        public array $executables = [],
        public bool $downloads_arrive = true,
        array $rules = [],
    ) {
        $factory = new Factory;
        $factory->fake(function (PendingProcess $process) use ($rules, $factory) {
            $this->commands[] = $process->command;
            foreach ($rules as [$text, $exit, $output]) {
                if (str_contains(implode(' ', $process->command), $text)) {
                    return $factory->result($output, '', $exit);
                }
            }

            return $factory->result();
        });

        parent::__construct($factory);
    }

    public function phpBinary(): string
    {
        return $this->php;
    }

    public function osFamily(): string
    {
        return $this->os;
    }

    public function extensionVersion(string $extension): ?string
    {
        return $this->versions[$extension] ?? (in_array($extension, $this->loaded, true) ? '0.10.0' : null);
    }

    public function installed(string $package): bool
    {
        return in_array($package, $this->installed, true);
    }

    public function home(): ?string
    {
        return $this->home;
    }

    public function exists(string $path): bool
    {
        return in_array($path, $this->files, true);
    }

    public function find(string $name, array $extra_directories = []): ?string
    {
        return $this->executables[$name] ?? null;
    }

    public function download(string $url, string $path): bool
    {
        $this->downloads[] = [$url, $path];

        return $this->downloads_arrive;
    }

    public function remove(string $path): void
    {
        $this->removed[] = $path;
    }
}
