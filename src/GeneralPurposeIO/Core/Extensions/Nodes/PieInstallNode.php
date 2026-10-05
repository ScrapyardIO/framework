<?php

namespace GeneralPurposeIO\Core\Extensions\Nodes;

use GeneralPurposeIO\Core\Host;
use GeneralPurposeIO\Core\Extensions\PiePackage;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;

use function Laravel\Prompts\confirm;

class PieInstallNode extends Node
{
    public function __construct(
        private Host $host = new Host,
    ) {
        parent::__construct();
    }

    public function prep(SharedBag $shared): mixed
    {
        return [
            'interactive' => $shared->interactive ?? false,
            'output' => $shared->output ?? null,
        ];
    }

    /**
     * Downloads pie.phar into the home directory, then has it check its own
     * release attestation. A copy that fails the check is removed. Nothing is
     * downloaded without a yes, so a non-interactive run ends here.
     *
     * @return array{pie: ?string, note: ?string}
     */
    public function exec(mixed $prepRes): mixed
    {
        $home = $this->host->home();

        if (is_null($home)) {
            return ['pie' => null, 'note' => 'PIE is not installed, and there is no home directory to install it into.'];
        }

        if (! $prepRes['interactive']) {
            return ['pie' => null, 'note' => 'PIE is not installed. Run this command interactively to install it.'];
        }

        $url = PiePackage::PHAR_URL->value;
        $path = $home.'/'.PiePackage::INSTALL_DIRECTORY->value.'/'.PiePackage::BINARY->value;

        $accepted = confirm(
            label: 'PIE, the PHP extension installer, is not installed. Install it?',
            hint: 'Downloads pie.phar from github.com/php/pie to ~/'.PiePackage::INSTALL_DIRECTORY->value.'/'.PiePackage::BINARY->value.'.',
        );

        if (! $accepted) {
            return ['pie' => null, 'note' => 'PIE is not installed, and you declined to install it.'];
        }

        if (! $this->host->download($url, $path)) {
            return ['pie' => null, 'note' => "PIE could not be downloaded from {$url}."];
        }

        [$exit] = $this->host->run([$this->host->phpBinary(), $path, 'self-verify'], $prepRes['output']);

        if ($exit !== 0) {
            $this->host->remove($path);

            return ['pie' => null, 'note' => 'The downloaded PIE failed its own verification and was removed.'];
        }

        return ['pie' => $path, 'note' => null];
    }

    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        if (is_null($execRes['pie'])) {
            $shared->extensions_note = $execRes['note'];

            return null;
        }

        $shared->pie_binary = $execRes['pie'];

        return 'select-extensions';
    }
}
