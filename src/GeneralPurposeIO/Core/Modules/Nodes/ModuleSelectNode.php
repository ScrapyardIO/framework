<?php

namespace GeneralPurposeIO\Core\Modules\Nodes;

use GeneralPurposeIO\Core\Modules\MicroscrapModule;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;

class ModuleSelectNode extends Node
{
    public function prep(SharedBag $shared): mixed
    {
        return [
            'states' => $shared->module_states,
            'selected' => $shared->modules_selected ?? null,
        ];
    }

    /**
     * The installable modules are listed and start selected; each one this machine cannot have is named above the
     * list with its reason. A module named on the command line is the selection, and nothing is asked.
     *
     * @return list<string> The selected MicroscrapModule values.
     */
    public function exec(mixed $prepRes): mixed
    {
        if (! is_null($prepRes['selected'])) {
            return $prepRes['selected'];
        }

        $options = [];
        $unavailable = [];
        foreach ($prepRes['states'] as $package => $reason) {
            $module = MicroscrapModule::from($package);

            if (is_null($reason)) {
                $options[$package] = "{$module->module()} — {$module->description()}";
            } else {
                $unavailable[] = "{$module->module()} ({$reason})";
            }
        }

        if ($unavailable !== []) {
            note('Not installable here: '.implode(', ', $unavailable).'.');
        }

        return array_values(multiselect(
            label: 'Modules to install',
            options: $options,
            default: array_keys($options),
            hint: 'Space to select, Enter to confirm.',
        ));
    }

    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        if ($execRes === []) {
            $shared->modules_note = 'None selected.';

            return null;
        }

        $shared->modules_selected = $execRes;

        return 'install-modules';
    }
}
