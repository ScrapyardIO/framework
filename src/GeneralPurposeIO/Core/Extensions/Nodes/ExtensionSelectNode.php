<?php

namespace GeneralPurposeIO\Core\Extensions\Nodes;

use GeneralPurposeIO\Core\Extensions\ScrapyardExtension;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;

class ExtensionSelectNode extends Node
{
    public function prep(SharedBag $shared): mixed
    {
        return [
            'states' => $shared->extension_states,
            'outdated' => $shared->extensions_outdated ?? [],
            'selected' => $shared->extensions_selected ?? null,
        ];
    }

    /**
     * The installable extensions are listed and start selected; each one that cannot be installed here is named
     * above the list with its reason. An extension named on the command line is the selection, and nothing is asked.
     *
     * @return list<string> The selected ScrapyardExtension values.
     */
    public function exec(mixed $prepRes): mixed
    {
        if (! is_null($prepRes['selected'])) {
            return $prepRes['selected'];
        }

        $options = [];
        $unavailable = [];
        foreach ($prepRes['states'] as $package => $reason) {
            $extension = ScrapyardExtension::from($package);

            if (is_null($reason)) {
                $outdated = isset($prepRes['outdated'][$package]) ? " ({$prepRes['outdated'][$package]} loaded, replaced by ^0.10)" : '';
                $options[$package] = "{$extension->extension()}{$outdated} — {$extension->description()}";
            } else {
                $unavailable[] = "{$extension->extension()} ({$reason})";
            }
        }

        if ($unavailable !== []) {
            note('Not installable here: '.implode(', ', $unavailable).'.');
        }

        return array_values(multiselect(
            label: 'Extensions to install',
            options: $options,
            default: array_keys($options),
            hint: 'Space to select, Enter to confirm.',
        ));
    }

    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        if ($execRes === []) {
            $shared->extensions_note = 'None selected.';

            return null;
        }

        $shared->extensions_selected = $execRes;

        return 'install-extensions';
    }
}
