<?php

namespace GeneralPurposeIO\Core\Extensions\Nodes;

use GeneralPurposeIO\Core\Host;
use GeneralPurposeIO\Core\Extensions\PiePackage;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;

class PieFinderNode extends Node
{
    public function __construct(
        private Host $host = new Host,
    ) {
        parent::__construct();
    }

    /**
     * A PIE counts only when the PHP binary running the command can run it.
     *
     * @return ?string Its path.
     */
    public function exec(mixed $prepRes): mixed
    {
        $home = $this->host->home();
        $pie = $this->host->find(
            PiePackage::BINARY->value,
            is_null($home) ? [] : [$home.'/'.PiePackage::INSTALL_DIRECTORY->value],
        );

        if (is_null($pie)) {
            return null;
        }

        [$exit, $version] = $this->host->run([$this->host->phpBinary(), $pie, '--version']);

        return $exit === 0 && str_contains($version, PiePackage::VERSION_MARKER->value) ? $pie : null;
    }

    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        if (! is_null($execRes)) {
            $shared->pie_binary = $execRes;

            return 'select-extensions';
        }

        return 'install-pie';
    }
}
