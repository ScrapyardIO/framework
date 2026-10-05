<?php

namespace GeneralPurposeIO\Core\Modules\Nodes;

use GeneralPurposeIO\Core\Host;
use GeneralPurposeIO\Core\Modules\MicroscrapModule;
use Throwable;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;

class ModuleInstallNode extends Node
{
    public function __construct(
        private Host $host = new Host,
    ) {
        parent::__construct();
    }

    public function prep(SharedBag $shared): mixed
    {
        return [
            'selected' => $shared->modules_selected,
            'composer_binary' => $shared->composer_binary,
            'base_path' => $shared->base_path,
            'output' => $shared->output ?? null,
        ];
    }

    /**
     * One `composer require` for every selected module, in the application's root, run by the PHP binary running
     * the command and on its terminal. Composer resolves them together, so they land together or not at all.
     *
     * @return array<string, string> Module short name => outcome.
     */
    public function exec(mixed $prepRes): mixed
    {
        $modules = array_map(fn (string $package): MicroscrapModule => MicroscrapModule::from($package), $prepRes['selected']);

        $command = [
            $this->host->phpBinary(),
            $prepRes['composer_binary'],
            'require',
            ...array_map(fn (MicroscrapModule $module): string => $module->requirement(), $modules),
            '--working-dir='.$prepRes['base_path'],
        ];

        try {
            [$exit] = $this->host->run($command, $prepRes['output'], terminal: true);
            $outcome = $exit === 0 ? 'installed' : "failed (Composer exit code {$exit})";
        } catch (Throwable $e) {
            $outcome = "failed ({$e->getMessage()})";
        }

        $results = [];
        foreach ($modules as $module) {
            $results[$module->module()] = $outcome;
        }

        return $results;
    }

    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        $shared->module_results = $execRes;

        return null;
    }
}
