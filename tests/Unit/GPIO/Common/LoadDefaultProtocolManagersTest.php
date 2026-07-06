<?php

use GPIO\Common\LoadDefaultProtocolManagers;
use GPIO\Contracts\Common\CarrierDriver;
use GPIO\Contracts\Common\CarrierDriverManager as CarrierDriverManagerContract;
use Microscrap\ScrapyardIODrivers\USB\ScrapyardIOUSBManager;
use ScrapyardIO\NutsAndBolts\Reflection;

// LoadDefaultProtocolManagers scans the sibling "microscrap/" folder directly
// off disk (see its hardcoded $dirname), not through this framework
// package's own composer autoloading. The framework doesn't (and shouldn't)
// depend on microscrap/usb-drivers itself, so ScrapyardIOUSBManager isn't
// autoloadable inside this isolated test run - and it and its own
// dependencies (MPSSE/FTDI adapters) are too deep to hand-require file by
// file. Instead, register the real application's autoloader (which has
// every sibling package properly psr-4 mapped via path repositories) - by
// the time real application code calls this Action, that's exactly the
// autoloader that's active anyway.
require_once dirname(__DIR__, 6).'/vendor/autoload.php';

test('it returns an array', function () {
    expect(LoadDefaultProtocolManagers::run())->toBeArray();
});

test('it discovers the real usb carrier manager', function () {
    $result = LoadDefaultProtocolManagers::run();

    expect($result)
        ->toHaveKey('usb')
        ->and($result['usb'])->toBe(ScrapyardIOUSBManager::class);
});

test('every discovered manager implements CarrierDriverManager and is not abstract', function () {
    $result = LoadDefaultProtocolManagers::run();

    expect($result)->not->toBeEmpty();

    foreach ($result as $class) {
        expect(is_subclass_of($class, CarrierDriverManagerContract::class))->toBeTrue()
            ->and((new ReflectionClass($class))->isAbstract())->toBeFalse();
    }
});

test('every discovered manager is keyed by its own CarrierDriver attribute value', function () {
    $result = LoadDefaultProtocolManagers::run();

    foreach ($result as $driver => $class) {
        $attribute = Reflection::reflect_class($class, CarrierDriver::class);

        expect($attribute)->not->toBeNull()
            ->and($attribute->newInstance()->driver)->toBe($driver);
    }
});
