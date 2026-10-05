<?php

use GeneralPurposeIO\Core\Modules\MicroscrapModule;
use GeneralPurposeIO\Core\Modules\ModulesFlow;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use ScrapyardIO\Tests\Fixtures\DescribedHost;
use Voyager\Workflows\SharedBag;

/*
 * The scrapyard:modules interview over a described machine: its operating system, the extensions the running PHP
 * loads, what the application already has. The Process factory's fake stands in for Composer.
 */

const MOD_PHP = '/opt/php/bin/php';
const MOD_COMPOSER = '/usr/local/bin/composer';
const MOD_APP = '/srv/app';

/** A machine with a Composer on PATH that this PHP runs. */
function modHost(array $args = []): DescribedHost
{
    $rules = [...($args['rules'] ?? []), ['--version', 0, 'Composer version 2.8.12 2025-09-19 13:41:59']];

    return new DescribedHost(...[...['executables' => ['composer' => MOD_COMPOSER]], ...$args, 'rules' => $rules]);
}

/** @param  list<string>  $keys */
function modInterview(DescribedHost $host, array $keys = [], ?string $only = null, bool $interactive = true): SharedBag
{
    Prompt::fake($keys);
    $shared = new SharedBag;
    $shared->interactive = $interactive;
    $shared->only = $only;
    $shared->base_path = MOD_APP;
    $shared->output = null;
    new ModulesFlow($host)->run($shared);

    return $shared;
}

/** @param  list<string>  $requirements */
function composerRequire(array $requirements, string $composer = MOD_COMPOSER): array
{
    return [MOD_PHP, $composer, 'require', ...$requirements, '--working-dir='.MOD_APP];
}

it('names each module by its package short name', function () {
    expect(MicroscrapModule::named('scrapyard-usb'))->toBe(MicroscrapModule::SCRAPYARD_USB)
        ->and(MicroscrapModule::named('GPIO'))->toBe(MicroscrapModule::GPIO)
        ->and(MicroscrapModule::named('microscrap/gpio'))->toBeNull()
        ->and(MicroscrapModule::MPSSE->requirement())->toBe('microscrap/mpsse:^0.10');
});

it('works out what the operating system and extensions allow', function (string $os, array $loaded, array $reasons) {
    $version = fn (string $extension): ?string => in_array($extension, $loaded, true) ? '0.10.0' : null;
    $actual = [];
    foreach (MicroscrapModule::cases() as $module) {
        $actual[$module->module()] = $module->unavailableOn($os, $version);
    }

    expect($actual)->toBe($reasons);
})->with([
    'Linux, posi' => ['Linux', ['posi'], ['gpio' => null, 'i2c' => null, 'spi' => null, 'uart' => null, 'mpsse' => 'needs ext-ftdi', 'scrapyard-linux' => null, 'scrapyard-usb' => 'needs ext-ftdi']],
    'Linux, both' => ['Linux', ['posi', 'ftdi'], ['gpio' => null, 'i2c' => null, 'spi' => null, 'uart' => null, 'mpsse' => null, 'scrapyard-linux' => null, 'scrapyard-usb' => null]],
    'macOS, ftdi' => ['Darwin', ['ftdi'], ['gpio' => 'needs ext-posi', 'i2c' => 'needs ext-posi', 'spi' => 'needs ext-posi', 'uart' => 'needs ext-posi', 'mpsse' => null, 'scrapyard-linux' => 'Linux only', 'scrapyard-usb' => null]],
    'macOS, both' => ['Darwin', ['posi', 'ftdi'], ['gpio' => null, 'i2c' => null, 'spi' => null, 'uart' => null, 'mpsse' => null, 'scrapyard-linux' => 'Linux only', 'scrapyard-usb' => null]],
    'neither' => ['Linux', [], ['gpio' => 'needs ext-posi', 'i2c' => 'needs ext-posi', 'spi' => 'needs ext-posi', 'uart' => 'needs ext-posi', 'mpsse' => 'needs ext-ftdi', 'scrapyard-linux' => 'needs ext-posi', 'scrapyard-usb' => 'needs ext-ftdi']],
]);

it('installs every module a Linux board with both extensions can run, in one composer require run by this PHP', function () {
    $host = modHost(['os' => 'Linux', 'loaded' => ['posi', 'ftdi']]);

    $shared = modInterview($host, [Key::ENTER]);

    expect($host->commands)->toBe([
        [MOD_PHP, MOD_COMPOSER, '--version', '--no-ansi'],
        composerRequire(array_map(fn (MicroscrapModule $module): string => $module->requirement(), MicroscrapModule::cases())),
    ])
        ->and($shared->module_results)->toBe(array_fill_keys(['gpio', 'i2c', 'spi', 'uart', 'mpsse', 'scrapyard-linux', 'scrapyard-usb'], 'installed'));
});

it('offers only the posi modules and scrapyard-linux on a Linux board with ext-posi', function () {
    $host = modHost(['os' => 'Linux', 'loaded' => ['posi']]);

    $shared = modInterview($host, [Key::ENTER]);

    expect($host->commands[1])->toBe(composerRequire(['microscrap/gpio:^0.10', 'microscrap/i2c:^0.10', 'microscrap/spi:^0.10', 'microscrap/uart:^0.10', 'microscrap/scrapyard-linux:^0.10']))
        ->and(Prompt::strippedContent())->toContain('Not installable here: mpsse (needs ext-ftdi), scrapyard-usb (needs ext-ftdi).');
});

it('offers mpsse and scrapyard-usb on a Mac with ext-ftdi, and says why scrapyard-linux is out', function () {
    $host = modHost(['os' => 'Darwin', 'loaded' => ['ftdi']]);

    $shared = modInterview($host, [Key::ENTER]);

    expect($host->commands[1])->toBe(composerRequire(['microscrap/mpsse:^0.10', 'microscrap/scrapyard-usb:^0.10']))
        ->and(Prompt::strippedContent())->toContain('scrapyard-linux (Linux only)')
        ->and($shared->module_results)->toBe(['mpsse' => 'installed', 'scrapyard-usb' => 'installed']);
});

it('leaves out what the application already has', function () {
    $host = modHost(['os' => 'Darwin', 'loaded' => ['ftdi'], 'installed' => ['microscrap/mpsse']]);

    $shared = modInterview($host, [Key::ENTER]);

    expect($shared->module_results)->toBe(['scrapyard-usb' => 'installed'])
        ->and(Prompt::strippedContent())->toContain('mpsse (installed)');
});

it('installs the one named module without showing the list', function () {
    $host = modHost(['loaded' => ['posi']]);

    $shared = modInterview($host, only: 'uart', interactive: false);

    expect($host->commands[1])->toBe(composerRequire(['microscrap/uart:^0.10']))
        ->and($shared->module_results)->toBe(['uart' => 'installed'])
        ->and(Prompt::strippedContent())->not->toContain('Modules to install');
});

it('ends with the reason when the named module cannot be installed', function (string $os, array $loaded, array $installed, string $only, string $note) {
    $host = modHost(['os' => $os, 'loaded' => $loaded, 'installed' => $installed]);

    expect(modInterview($host, only: $only)->modules_note)->toBe($note)
        ->and($host->commands)->toBe([]);
})->with([
    'missing extension' => ['Linux', [], [], 'gpio', 'Nothing to install: gpio (needs ext-posi).'],
    'another operating system' => ['Darwin', ['posi'], [], 'scrapyard-linux', 'Nothing to install: scrapyard-linux (Linux only).'],
    'already installed' => ['Linux', ['posi'], ['microscrap/spi'], 'spi', 'Nothing to install: spi (installed).'],
    'unknown' => ['Linux', ['posi'], [], 'rpc', 'Unknown module [rpc]. Choose one of: gpio, i2c, spi, uart, mpsse, scrapyard-linux, scrapyard-usb.'],
]);

it('ends when the machine has neither extension', function () {
    $host = modHost(['os' => 'Darwin']);

    expect(modInterview($host)->modules_note)
        ->toBe('Nothing to install: gpio (needs ext-posi), i2c (needs ext-posi), spi (needs ext-posi), uart (needs ext-posi), mpsse (needs ext-ftdi), scrapyard-linux (Linux only), scrapyard-usb (needs ext-ftdi).')
        ->and($host->commands)->toBe([]);
});

it('asks for a name on a non-interactive run with none', function () {
    expect(modInterview(modHost(['loaded' => ['posi']]), interactive: false)->modules_note)
        ->toBe('Name the module to install on a non-interactive run: gpio, i2c, spi, uart, mpsse, scrapyard-linux, scrapyard-usb.');
});

it('ends when nothing is selected', function () {
    $host = modHost(['loaded' => ['ftdi']]);

    $shared = modInterview($host, [Key::CTRL_A, Key::ENTER]);

    expect($shared->modules_note)->toBe('None selected.')
        ->and($host->commands)->toBe([[MOD_PHP, MOD_COMPOSER, '--version', '--no-ansi']]);
});

it('falls back to composer.phar in the application root', function () {
    $host = modHost(['executables' => [], 'files' => [MOD_APP.'/composer.phar'], 'loaded' => ['ftdi']]);

    $shared = modInterview($host, only: 'mpsse');

    expect($host->commands[1])->toBe(composerRequire(['microscrap/mpsse:^0.10'], MOD_APP.'/composer.phar'))
        ->and($shared->module_results)->toBe(['mpsse' => 'installed']);
});

it('ends when no Composer this PHP can run is found', function (array $args) {
    $host = modHost([...['loaded' => ['ftdi']], ...$args]);

    expect(modInterview($host, only: 'mpsse')->modules_note)
        ->toBe('No Composer that '.MOD_PHP.' can run was found on PATH or as composer.phar in '.MOD_APP.'. Install Composer from getcomposer.org first.');
})->with([
    'none on the machine' => [['executables' => []]],
    'one this PHP cannot run' => [['rules' => [['--version', 1, '']]]],
    'something else named composer' => [['rules' => [['--version', 0, 'not the dependency manager']]]],
]);

it('refuses the modules of an extension loaded off the ^0.10 line, naming the version', function () {
    $host = modHost(['os' => 'Darwin', 'loaded' => ['posi'], 'versions' => ['ftdi' => '0.9.0']]);

    $shared = modInterview($host, [Key::ENTER]);

    expect(Prompt::strippedContent())->toContain('mpsse (needs ext-ftdi ^0.10, 0.9.0 is loaded)')
        ->and($host->commands[1])->toBe(composerRequire(['microscrap/gpio:^0.10', 'microscrap/i2c:^0.10', 'microscrap/spi:^0.10', 'microscrap/uart:^0.10']));
});

it('reports a failed composer require against every module it carried', function () {
    $host = modHost(['os' => 'Darwin', 'loaded' => ['ftdi'], 'rules' => [['require', 2, '']]]);

    expect(modInterview($host, [Key::ENTER])->module_results)
        ->toBe(['mpsse' => 'failed (Composer exit code 2)', 'scrapyard-usb' => 'failed (Composer exit code 2)']);
});
