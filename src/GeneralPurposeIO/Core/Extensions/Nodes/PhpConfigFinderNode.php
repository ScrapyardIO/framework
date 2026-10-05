<?php

namespace GeneralPurposeIO\Core\Extensions\Nodes;

use GeneralPurposeIO\Core\Host;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;

/**
 * Finds the php-config that builds for the PHP binary running the command,
 * so PIE builds for that binary and no other.
 */
class PhpConfigFinderNode extends Node
{
    public function __construct(
        private Host $host = new Host,
    ) {
        parent::__construct();
    }

    /**
     * Tries the php-config beside the binary (php8.4 → php-config8.4), then the
     * one on PATH. Each says which binary it builds for.
     *
     * @return array{php_config: ?string, other: ?string} `other` is the binary a
     *                                                     php-config builds for when none builds for this one.
     */
    public function exec(mixed $prepRes): mixed
    {
        $php = $this->host->phpBinary();
        $beside = dirname($php).'/'.preg_replace('/php/', 'php-config', basename($php), 1);
        $other = null;

        foreach (array_unique(array_filter([$beside, $this->host->find('php-config')])) as $candidate) {
            [$exit, $builds_for] = $this->host->run([$candidate, '--php-binary']);

            if ($exit !== 0 || $builds_for === '') {
                continue;
            }

            if ($this->resolved($builds_for) === $this->resolved($php)) {
                return ['php_config' => $candidate, 'other' => null];
            }

            $other = $builds_for;
        }

        return ['php_config' => null, 'other' => $other];
    }

    /**
     * With no php-config on the machine the flow goes on: PIE checks for build
     * tools itself and offers to install PHP's development files.
     */
    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        if (is_null($execRes['php_config']) && ! is_null($execRes['other'])) {
            $php = $this->host->phpBinary();
            $shared->extensions_note = "The php-config on this machine builds for {$execRes['other']}, not for {$php}. Install the development files for {$php} first.";

            return null;
        }

        $shared->php_config = $execRes['php_config'];

        return 'find-pie';
    }

    private function resolved(string $path): string
    {
        $real = realpath($path);

        return $real === false ? $path : $real;
    }
}
