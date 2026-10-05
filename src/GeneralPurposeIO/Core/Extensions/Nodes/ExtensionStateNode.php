<?php

namespace GeneralPurposeIO\Core\Extensions\Nodes;

use GeneralPurposeIO\Core\Extensions\ScrapyardExtension;
use GeneralPurposeIO\Core\Host;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;

/**
 * Works out what can be installed here. An extension loaded at a version off the ^0.10 line counts as installable,
 * and PIE replaces it. With one extension named, that one is the selection and the list is never shown.
 */
class ExtensionStateNode extends Node
{
    public function __construct(
        private Host $host = new Host,
    ) {
        parent::__construct();
    }

    public function prep(SharedBag $shared): mixed
    {
        $states = [];
        $outdated = [];
        foreach (ScrapyardExtension::cases() as $extension) {
            $version = $this->host->extensionVersion($extension->extension());
            $current = ! is_null($version) && ScrapyardExtension::accepts($version);

            $states[$extension->value] = $extension->unsupportedOn($this->host->osFamily()) ?? ($current ? 'installed' : null);

            if (! is_null($version) && ! $current) {
                $outdated[$extension->value] = $version;
            }
        }

        return [
            'states' => $states,
            'outdated' => $outdated,
            'only' => $shared->only ?? null,
            'interactive' => $shared->interactive ?? false,
        ];
    }

    /**
     * @return array{note: ?string, selected: ?list<string>}
     */
    public function exec(mixed $prepRes): mixed
    {
        $names = array_map(fn (ScrapyardExtension $extension): string => $extension->extension(), ScrapyardExtension::cases());

        if (! is_null($prepRes['only'])) {
            $extension = ScrapyardExtension::named($prepRes['only']);

            if (is_null($extension)) {
                return ['note' => "Unknown extension [{$prepRes['only']}]. Choose one of: ".implode(', ', $names).'.', 'selected' => null];
            }

            $reason = $prepRes['states'][$extension->value];

            return is_null($reason)
                ? ['note' => null, 'selected' => [$extension->value]]
                : ['note' => "Nothing to install: {$extension->extension()} ({$reason}).", 'selected' => null];
        }

        if (! in_array(null, $prepRes['states'], true)) {
            $reasons = [];
            foreach ($prepRes['states'] as $package => $reason) {
                $reasons[] = ScrapyardExtension::from($package)->extension()." ({$reason})";
            }

            return ['note' => 'Nothing to install: '.implode(', ', $reasons).'.', 'selected' => null];
        }

        if (! $prepRes['interactive']) {
            return ['note' => 'Name the extension to install on a non-interactive run: '.implode(', ', $names).'.', 'selected' => null];
        }

        return ['note' => null, 'selected' => null];
    }

    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        $shared->extension_states = $prepRes['states'];
        $shared->extensions_outdated = $prepRes['outdated'];

        if (! is_null($execRes['note'])) {
            $shared->extensions_note = $execRes['note'];

            return null;
        }

        if (! is_null($execRes['selected'])) {
            $shared->extensions_selected = $execRes['selected'];
        }

        return 'find-php-config';
    }
}
