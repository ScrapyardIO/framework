<?php

use GeneralPurposeIO\Core\Extensions\ExtensionsFlow;
use GeneralPurposeIO\Core\Extensions\ScrapyardExtension;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use ScrapyardIO\Tests\Fixtures\DescribedHost;
use Voyager\Workflows\SharedBag;

/*
 * The scrapyard:ext interview over a described machine. DescribedHost says what is there, the Process factory's fake
 * answers each command, Prompt::fake() presses keys. No PIE, no network.
 */

const SY_PHP = '/opt/php/bin/php';
const SY_PIE = '/usr/local/bin/pie';
const SY_PHP_CONFIG = '/opt/php/bin/php-config';

/** A machine with PIE on PATH and a php-config beside the binary that builds for it. */
function extHost(array $args = []): DescribedHost
{
    $rules = [...($args['rules'] ?? []), ["echo phpversion('", 0, '0.10.0'], ['--version', 0, '🥧 PHP Installer for Extensions (PIE) 1.5.1'], [SY_PHP_CONFIG.' --php-binary', 0, SY_PHP], ['--php-binary', 127, '']];

    return new DescribedHost(...[...['executables' => ['pie' => SY_PIE]], ...$args, 'rules' => $rules]);
}

/** @param  list<string>  $keys */
function extInterview(DescribedHost $host, array $keys = [], ?string $only = null, bool $interactive = true): SharedBag
{
    Prompt::fake($keys);
    $shared = new SharedBag;
    $shared->interactive = $interactive;
    $shared->only = $only;
    $shared->output = null;
    new ExtensionsFlow($host)->run($shared);

    return $shared;
}

it('finds a case by the name PHP loads it under', function () {
    expect(ScrapyardExtension::named('posi'))->toBe(ScrapyardExtension::POSI)
        ->and(ScrapyardExtension::named('FTDI'))->toBe(ScrapyardExtension::FTDI)
        ->and(ScrapyardExtension::named('gd'))->toBeNull();
});

it('installs both extensions from the list, each through PIE run by this PHP, each checked', function () {
    $host = extHost();

    $shared = extInterview($host, [Key::ENTER]);

    expect($host->commands)->toBe([
        [SY_PHP_CONFIG, '--php-binary'],
        [SY_PHP, SY_PIE, '--version'],
        [SY_PHP, SY_PIE, 'install', 'php-io-extensions/posi:^0.10', '--with-php-config='.SY_PHP_CONFIG],
        [SY_PHP, '-r', "echo phpversion('posi');"],
        [SY_PHP, SY_PIE, 'install', 'php-io-extensions/ftdi:^0.10', '--with-php-config='.SY_PHP_CONFIG],
        [SY_PHP, '-r', "echo phpversion('ftdi');"],
    ])
        ->and($shared->extension_results)->toBe(['posi' => 'installed', 'ftdi' => 'installed']);
});

it('names a loaded extension above the list and lists only the other', function () {
    $host = extHost(['os' => 'Darwin', 'loaded' => ['posi']]);

    $shared = extInterview($host, [Key::ENTER]);

    expect($shared->extension_results)->toBe(['ftdi' => 'installed'])
        ->and(Prompt::strippedContent())->toContain('Not installable here: posi (installed).')
        ->and(Prompt::strippedContent())->toContain('ftdi — FTDI USB bridges');
});

it('installs the one named extension without showing the list', function () {
    $host = extHost();

    $shared = extInterview($host, only: 'ftdi', interactive: false);

    expect($shared->extension_results)->toBe(['ftdi' => 'installed'])
        ->and($host->commands)->not->toContain([SY_PHP, SY_PIE, 'install', 'php-io-extensions/posi:^0.10', '--with-php-config='.SY_PHP_CONFIG])
        ->and(Prompt::strippedContent())->not->toContain('Extensions to install');
});

it('ends with the reason when the named extension cannot be installed', function (string $os, string $only, array $loaded, string $note) {
    $host = extHost(['os' => $os, 'loaded' => $loaded]);

    $shared = extInterview($host, only: $only);

    expect($shared->extensions_note)->toBe($note)
        ->and($host->commands)->toBe([]);
})->with([
    'Windows' => ['Windows', 'posi', [], 'Nothing to install: posi (not on Windows).'],
    'already loaded' => ['Linux', 'ftdi', ['ftdi'], 'Nothing to install: ftdi (installed).'],
    'not a scrapyard extension' => ['Linux', 'epoll', [], 'Unknown extension [epoll]. Choose one of: posi, ftdi.'],
]);

it('ends when both are loaded, or on Windows', function (string $os, array $loaded, string $note) {
    $host = extHost(['os' => $os, 'loaded' => $loaded]);

    expect(extInterview($host)->extensions_note)->toBe($note)
        ->and($host->commands)->toBe([]);
})->with([
    'both loaded' => ['Linux', ['posi', 'ftdi'], 'Nothing to install: posi (installed), ftdi (installed).'],
    'Windows' => ['Windows', [], 'Nothing to install: posi (not on Windows), ftdi (not on Windows).'],
]);

it('asks for a name on a non-interactive run with none', function () {
    expect(extInterview(extHost(), interactive: false)->extensions_note)
        ->toBe('Name the extension to install on a non-interactive run: posi, ftdi.');
});

it('ends when nothing is selected', function () {
    $host = extHost();

    // Ctrl+A toggles every row off, Enter confirms the empty selection.
    $shared = extInterview($host, [Key::CTRL_A, Key::ENTER]);

    expect($shared->extensions_note)->toBe('None selected.')
        ->and($host->commands)->not->toContain([SY_PHP, '-r', "echo phpversion('posi');"]);
});

it('offers to download PIE, has it verify itself, and installs with it', function () {
    $host = extHost(['executables' => []]);

    $shared = extInterview($host, ['y', Key::ENTER], only: 'posi');

    expect($host->downloads)->toBe([['https://github.com/php/pie/releases/latest/download/pie.phar', '/home/dev/.local/bin/pie']])
        ->and($host->commands)->toContain([SY_PHP, '/home/dev/.local/bin/pie', 'self-verify'])
        ->and($host->commands)->toContain([SY_PHP, '/home/dev/.local/bin/pie', 'install', 'php-io-extensions/posi:^0.10', '--with-php-config='.SY_PHP_CONFIG])
        ->and($shared->extension_results)->toBe(['posi' => 'installed']);
});

it('ends without PIE when it cannot be had', function (array $args, array $keys, bool $interactive, string $note) {
    $host = extHost([...['executables' => []], ...$args]);

    $shared = extInterview($host, $keys, only: 'posi', interactive: $interactive);

    expect($shared->extensions_note)->toBe($note);
})->with([
    'non-interactive' => [[], [], false, 'PIE is not installed. Run this command interactively to install it.'],
    'declined' => [[], ['n', Key::ENTER], true, 'PIE is not installed, and you declined to install it.'],
    'no home directory' => [['home' => null], [], true, 'PIE is not installed, and there is no home directory to install it into.'],
    'download fails' => [['downloads_arrive' => false], ['y', Key::ENTER], true, 'PIE could not be downloaded from https://github.com/php/pie/releases/latest/download/pie.phar.'],
    'fails its own verification' => [['rules' => [['self-verify', 1, '']]], ['y', Key::ENTER], true, 'The downloaded PIE failed its own verification and was removed.'],
]);

it('does not count a PIE this PHP cannot run', function () {
    $host = extHost(['rules' => [[SY_PIE.' --version', 255, '']]]);

    expect(extInterview($host, only: 'posi', interactive: false)->extensions_note)
        ->toBe('PIE is not installed. Run this command interactively to install it.');
});

it('ends when the only php-config builds for another PHP', function () {
    $host = extHost([
        'executables' => ['pie' => SY_PIE, 'php-config' => '/usr/local/bin/php-config'],
        'rules' => [[SY_PHP_CONFIG.' --php-binary', 127, ''], ['/usr/local/bin/php-config --php-binary', 0, '/usr/local/bin/php']],
    ]);

    expect(extInterview($host, only: 'posi')->extensions_note)
        ->toBe('The php-config on this machine builds for /usr/local/bin/php, not for '.SY_PHP.'. Install the development files for '.SY_PHP.' first.');
});

it('leaves the php-config to PIE when the machine has none', function () {
    $host = extHost(['rules' => [[SY_PHP_CONFIG.' --php-binary', 127, '']]]);

    $shared = extInterview($host, only: 'ftdi');

    expect($shared->php_config)->toBeNull()
        ->and($host->commands)->toContain([SY_PHP, SY_PIE, 'install', 'php-io-extensions/ftdi:^0.10'])
        ->and($shared->extension_results)->toBe(['ftdi' => 'installed']);
});

it('reports a failed build and still installs the next extension', function () {
    $host = extHost(['rules' => [['install php-io-extensions/posi', 2, '']]]);

    $shared = extInterview($host, [Key::ENTER]);

    expect($shared->extension_results)->toBe(['posi' => 'failed (PIE exit code 2)', 'ftdi' => 'installed'])
        ->and($host->commands)->not->toContain([SY_PHP, '-r', "echo phpversion('posi');"]);
});

it('reports an extension PIE installed that this PHP still does not load, or loads off the ^0.10 line', function (string $loaded, string $outcome) {
    $host = extHost(['rules' => [["phpversion('ftdi')", 0, $loaded]]]);

    expect(extInterview($host, only: 'ftdi')->extension_results)->toBe(['ftdi' => $outcome]);
})->with([
    'not loaded' => ['', 'installed by PIE, but '.SY_PHP.' does not load it'],
    'old version' => ['0.9.0', 'installed by PIE, but '.SY_PHP.' loads 0.9.0'],
]);

it('offers an extension loaded off the ^0.10 line as a replacement', function () {
    $host = extHost(['os' => 'Darwin', 'loaded' => ['posi'], 'versions' => ['ftdi' => '0.9.0']]);

    $shared = extInterview($host, [Key::ENTER]);

    expect(Prompt::strippedContent())->toContain('ftdi (0.9.0 loaded, replaced by ^0.10) — FTDI USB')
        ->and($shared->extension_results)->toBe(['ftdi' => 'installed']);
});

it('installs a named extension loaded off the ^0.10 line', function () {
    $host = extHost(['versions' => ['posi' => '0.9.2']]);

    expect(extInterview($host, only: 'posi', interactive: false)->extension_results)->toBe(['posi' => 'installed']);
});

it('takes only the ^0.10 line', function (string $version, bool $accepted) {
    expect(ScrapyardExtension::accepts($version))->toBe($accepted);
})->with([
    ['0.10.0', true], ['0.10.7', true], ['0.9.0', false], ['0.11.0', false], ['1.0.0', false],
]);
