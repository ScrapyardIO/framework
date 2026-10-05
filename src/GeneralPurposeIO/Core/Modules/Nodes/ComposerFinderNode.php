<?php

namespace GeneralPurposeIO\Core\Modules\Nodes;

use GeneralPurposeIO\Core\Host;
use GeneralPurposeIO\Core\Modules\ComposerPackage;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;

class ComposerFinderNode extends Node
{
    public function __construct(
        private Host $host = new Host,
    ) {
        parent::__construct();
    }

    public function prep(SharedBag $shared): mixed
    {
        return $shared->base_path;
    }

    /**
     * Composer on PATH, then composer.phar in the application's root. One counts only when the PHP binary running the
     * command can run it: Composer checks each package's ext-* requirements against the PHP that runs it.
     *
     * @return ?string Its path.
     */
    public function exec(mixed $prepRes): mixed
    {
        $phar = $prepRes.'/'.ComposerPackage::PHAR->value;
        $candidates = array_filter([
            $this->host->find(ComposerPackage::BINARY->value),
            $this->host->exists($phar) ? $phar : null,
        ]);

        foreach ($candidates as $composer) {
            [$exit, $version] = $this->host->run([$this->host->phpBinary(), $composer, '--version', '--no-ansi']);

            if ($exit === 0 && str_contains($version, ComposerPackage::VERSION_MARKER->value)) {
                return $composer;
            }
        }

        return null;
    }

    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        if (is_null($execRes)) {
            $php = $this->host->phpBinary();
            $shared->modules_note = "No Composer that {$php} can run was found on PATH or as composer.phar in {$prepRes}. Install Composer from getcomposer.org first.";

            return null;
        }

        $shared->composer_binary = $execRes;

        return 'select-modules';
    }
}
