<?php

namespace GeneralPurposeIO\Core\Modules\Nodes;

use GeneralPurposeIO\Core\Host;
use GeneralPurposeIO\Core\Modules\MicroscrapModule;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;

/**
 * Works out what this machine can have: the operating system, the extensions the running PHP binary loads, and
 * what the application already has. With one module named, that one is the selection and the list is never shown.
 */
class ModuleStateNode extends Node
{
    public function __construct(
        private Host $host = new Host,
    ) {
        parent::__construct();
    }

    public function prep(SharedBag $shared): mixed
    {
        $states = [];
        foreach (MicroscrapModule::cases() as $module) {
            $states[$module->value] = $module->unavailableOn($this->host->osFamily(), $this->host->extensionVersion(...))
                ?? ($this->host->installed($module->value) ? 'installed' : null);
        }

        return [
            'states' => $states,
            'only' => $shared->only ?? null,
            'interactive' => $shared->interactive ?? false,
        ];
    }

    /**
     * @return array{note: ?string, selected: ?list<string>}
     */
    public function exec(mixed $prepRes): mixed
    {
        $names = array_map(fn (MicroscrapModule $module): string => $module->module(), MicroscrapModule::cases());

        if (! is_null($prepRes['only'])) {
            $module = MicroscrapModule::named($prepRes['only']);

            if (is_null($module)) {
                return ['note' => "Unknown module [{$prepRes['only']}]. Choose one of: ".implode(', ', $names).'.', 'selected' => null];
            }

            $reason = $prepRes['states'][$module->value];

            return is_null($reason)
                ? ['note' => null, 'selected' => [$module->value]]
                : ['note' => "Nothing to install: {$module->module()} ({$reason}).", 'selected' => null];
        }

        if (! in_array(null, $prepRes['states'], true)) {
            $reasons = [];
            foreach ($prepRes['states'] as $package => $reason) {
                $reasons[] = MicroscrapModule::from($package)->module()." ({$reason})";
            }

            return ['note' => 'Nothing to install: '.implode(', ', $reasons).'.', 'selected' => null];
        }

        if (! $prepRes['interactive']) {
            return ['note' => 'Name the module to install on a non-interactive run: '.implode(', ', $names).'.', 'selected' => null];
        }

        return ['note' => null, 'selected' => null];
    }

    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        $shared->module_states = $prepRes['states'];

        if (! is_null($execRes['note'])) {
            $shared->modules_note = $execRes['note'];

            return null;
        }

        if (! is_null($execRes['selected'])) {
            $shared->modules_selected = $execRes['selected'];
        }

        return 'find-composer';
    }
}
