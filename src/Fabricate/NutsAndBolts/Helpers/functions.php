<?php

namespace Fabricate\NutsAndBolts\Helpers;

use Fabricate\NutsAndBolts\Defer\DeferredCallback;
use Fabricate\NutsAndBolts\Defer\DeferredCallbackCollection;
use Symfony\Component\Process\PhpExecutableFinder;

if (! function_exists('Fabricate\NutsAndBolts\Helpers\workshop_binary')) {
    /**
     * Determine the proper Workshop executable.
     */
    function workshop_binary(): string
    {
        return defined('WORKSHOP_BINARY') ? WORKSHOP_BINARY : 'workshop';
    }
}

if (! function_exists('Fabricate\NutsAndBolts\Helpers\php_binary')) {
    /**
     * Determine the PHP Binary.
     */
    function php_binary(): string
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}

if (! function_exists('Fabricate\NutsAndBolts\Helpers\defer')) {
    /**
     * Defer execution of the given callback.
     *
     * @param  callable|null  $callback
     * @param  string|null  $name
     * @param  bool  $always
     * @return ($callback is null ? DeferredCallbackCollection : \Fabricate\NutsAndBolts\Defer\DeferredCallback)
     */
    function defer(?callable $callback = null, ?string $name = null, bool $always = false): DeferredCallback|DeferredCallbackCollection
    {
        if ($callback === null) {
            return app(DeferredCallbackCollection::class);
        }

        return tap(
            new DeferredCallback($callback, $name, $always),
            fn ($deferred) => app(DeferredCallbackCollection::class)[] = $deferred
        );
    }
}