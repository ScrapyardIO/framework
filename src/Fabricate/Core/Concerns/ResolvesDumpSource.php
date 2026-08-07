<?php

namespace Fabricate\Core\Concerns;

use Fabricate\NutsAndBolts\Str;
use Throwable;

/**
 * @property string $basePath
 * @property string $compiledViewPath
 */
trait ResolvesDumpSource
{
    /**
     * Every href format for common editors.
     *
     * @var array<string, string>
     */
    protected array $editorHrefs = [
        'antigravity' => 'antigravity://file/{file}:{line}',
        'atom' => 'atom://core/open/file?filename={file}&line={line}',
        'cursor' => 'cursor://file/{file}:{line}',
        'emacs' => 'emacs://open?url=file://{file}&line={line}',
        'fleet' => 'fleet://open?file={file}&line={line}',
        'idea' => 'idea://open?file={file}&line={line}',
        'kiro' => 'kiro://file/{file}:{line}',
        'macvim' => 'mvim://open/?url=file://{file}&line={line}',
        'neovim' => 'nvim://open?url=file://{file}&line={line}',
        'netbeans' => 'netbeans://open/?f={file}:{line}',
        'nova' => 'nova://core/open/file?filename={file}&line={line}',
        'phpstorm' => 'phpstorm://open?file={file}&line={line}',
        'sublime' => 'subl://open?url=file://{file}&line={line}',
        'textmate' => 'txmt://open?url=file://{file}&line={line}',
        'trae' => 'trae://file/{file}:{line}',
        'vscode' => 'vscode://file/{file}:{line}',
        'vscode-insiders' => 'vscode-insiders://file/{file}:{line}',
        'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/{file}:{line}',
        'vscode-remote' => 'vscode://vscode-remote/{file}:{line}',
        'vscodium' => 'vscodium://file/{file}:{line}',
        'windsurf' => 'windsurf://file/{file}:{line}',
        'xdebug' => 'xdebug://{file}@{line}',
        'zed' => 'zed://file/{file}:{line}',
    ];

    /**
     * Files that require special trace handling and their levels.
     *
     * @var array<string, int>
     */
    protected static array $adjustableTraces = [
        'symfony/var-dumper/Resources/functions/dump.php' => 1,
        'Illuminate/Collections/Traits/EnumeratesValues.php' => 4,
        'Fabricate/Collections/Concerns/EnumeratesValues.php' => 4,
    ];

    /**
     * The source resolver.
     *
     * `false` disables source resolution; `null` uses the default backtrace.
     *
     * @var (callable(): (?array{0: string, 1: string, 2: int|null}))|null|false
     */
    protected static mixed $dumpSourceResolver = null;

    /**
     * Resolve the source of the dump call.
     *
     * @return array{0: string, 1: string, 2: int|null}|null
     */
    public function resolveDumpSource(): ?array
    {
        if (static::$dumpSourceResolver === false) {
            return null;
        }

        if (! is_null(static::$dumpSourceResolver)) {
            /** @var callable(): (?array{0: string, 1: string, 2: int|null}) $resolver */
            $resolver = static::$dumpSourceResolver;

            return $resolver();
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        $sourceKey = null;

        foreach ($trace as $traceKey => $traceFile) {
            if (! isset($traceFile['file'])) {
                continue;
            }

            foreach (self::$adjustableTraces as $name => $key) {
                if (str_ends_with(
                    $traceFile['file'],
                    str_replace('/', DIRECTORY_SEPARATOR, $name)
                )) {
                    $sourceKey = $traceKey + $key;
                    break;
                }
            }

            if (! is_null($sourceKey)) {
                break;
            }
        }

        if (is_null($sourceKey)) {
            return null;
        }

        $file = $trace[$sourceKey]['file'] ?? null;
        $line = $trace[$sourceKey]['line'] ?? null;

        if (is_null($file) || is_null($line)) {
            return null;
        }

        $relativeFile = $file;

        if ($this->isCompiledViewFile($file)) {
            $file = $this->getOriginalFileForCompiledView($file);
            $line = null;
        }

        if (str_starts_with($file, $this->basePath)) {
            $relativeFile = substr($file, strlen($this->basePath) + 1);
        }

        return [$file, $relativeFile, $line];
    }

    /**
     * Determine if the given file is a compiled view.
     */
    protected function isCompiledViewFile(string $file): bool
    {
        if ($this->compiledViewPath === '') {
            return false;
        }

        return str_starts_with($file, $this->compiledViewPath) && str_ends_with($file, '.php');
    }

    /**
     * Get the original view file for the given compiled file.
     */
    protected function getOriginalFileForCompiledView(string $file): string
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            return $file;
        }

        preg_match('/\/\*\*PATH\s(.*)\sENDPATH/', $contents, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        return $file;
    }

    /**
     * Resolve the source href, if possible.
     */
    protected function resolveSourceHref(string $file, ?int $line): ?string
    {
        $editor = null;

        try {
            $editor = config('app.editor');
        } catch (Throwable) {
            // Config may not be bound yet during early bootstrap.
        }

        if (is_null($editor)) {
            return null;
        }

        $href = is_array($editor) && isset($editor['href'])
            ? $editor['href']
            : ($this->editorHrefs[$editor['name'] ?? $editor] ?? sprintf('%s://open?file={file}&line={line}', $editor['name'] ?? $editor));

        if (! is_string($href)) {
            return null;
        }

        $basePath = is_array($editor) ? ($editor['base_path'] ?? false) : false;

        if (is_string($basePath)) {
            $file = Str::replaceStart($this->basePath, $basePath, $file);
        }

        return str_replace(
            ['{file}', '{line}'],
            [$file, is_null($line) ? '1' : (string) $line],
            $href,
        );
    }

    /**
     * Set the resolver that resolves the source of the dump call.
     *
     * @param  (callable(): (?array{0: string, 1: string, 2: int|null}))|null  $callable
     */
    public static function resolveDumpSourceUsing(?callable $callable): void
    {
        static::$dumpSourceResolver = $callable;
    }

    /**
     * Don't include the location / file of the dump in dumps.
     */
    public static function dontIncludeSource(): void
    {
        static::$dumpSourceResolver = false;
    }
}
