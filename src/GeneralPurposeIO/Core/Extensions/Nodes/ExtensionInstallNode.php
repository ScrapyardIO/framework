<?php

namespace GeneralPurposeIO\Core\Extensions\Nodes;

use Throwable;
use GeneralPurposeIO\Core\Extensions\ScrapyardExtension;
use GeneralPurposeIO\Core\Host;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;

class ExtensionInstallNode extends Node
{
    public function __construct(
        private Host $host = new Host,
    ) {
        parent::__construct();
    }

    public function prep(SharedBag $shared): mixed
    {
        return [
            'selected' => $shared->extensions_selected,
            'pie_binary' => $shared->pie_binary,
            'php_config' => $shared->php_config ?? null,
            'output' => $shared->output ?? null,
        ];
    }

    /**
     * One `pie install` per extension, run by the PHP binary running the
     * command and on its terminal, so PIE can ask for a sudo password or for
     * missing build tools. A failed build, or a PIE that cannot be started,
     * does not stop the ones after it. Each install is then checked by asking
     * that binary which version of the extension it loads.
     *
     * @return array<string, string> Extension name => outcome.
     */
    public function exec(mixed $prepRes): mixed
    {
        $php = $this->host->phpBinary();
        $results = [];

        foreach ($prepRes['selected'] as $package) {
            $name = ScrapyardExtension::from($package)->extension();

            $command = [$php, $prepRes['pie_binary'], 'install', $package];
            if (! is_null($prepRes['php_config'])) {
                $command[] = '--with-php-config='.$prepRes['php_config'];
            }

            try {
                [$exit] = $this->host->run($command, $prepRes['output'], terminal: true);

                if ($exit !== 0) {
                    $results[$name] = "failed (PIE exit code {$exit})";

                    continue;
                }

                [, $loaded] = $this->host->run([$php, '-r', "echo phpversion('{$name}');"]);

                $results[$name] = match (true) {
                    $loaded === '' => "installed by PIE, but {$php} does not load it",
                    ! ScrapyardExtension::accepts($loaded) => "installed by PIE, but {$php} loads {$loaded}",
                    default => 'installed',
                };
            } catch (Throwable $e) {
                $results[$name] = "failed ({$e->getMessage()})";
            }
        }

        return $results;
    }

    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        $shared->extension_results = $execRes;

        return null;
    }
}
