<?php

namespace BareMetal\Core;

use BareMetal\Contracts\Filesystem\FileNotFoundException;
use Exception;
use BareMetal\Filesystem\Filesystem;
use ScrpayardIO\NutsAndBolts\Collection;
use ScrapyardIO\NutsAndBolts\Env;

class PackageManifest
{
    /**
     * The filesystem instance.
     */
    public Filesystem $files;

    /**
     * The base path.
     */
    public string $base_path;

    /**
     * The vendor path.
     */
    public string $vendor_path;

    /**
     * The manifest path.
     */
    public ?string $manifest_path;

    /**
     * The loaded manifest array.
     */
    public array $manifest;

    /**
     * Create a new package manifest instance.
     */
    public function __construct(Filesystem $files, string $base_path, string $manifest_path)
    {
        $this->files = $files;
        $this->base_path = $base_path;
        $this->manifest_path = $manifest_path;
        $this->vendor_path = Env::get('COMPOSER_VENDOR_DIR') ?: $base_path.'/vendor';
    }

    /**
     * Get every service provider class name for all packages.
     */
    public function providers(): array
    {
        return $this->config('providers');
    }

    /**
     * Get every alias for all packages.
     * @throws FileNotFoundException
     */
    public function aliases(): array
    {
        return $this->config('aliases');
    }

    /**
     * Get every value for all packages for the given configuration name.
     * @throws FileNotFoundException
     */
    public function config(string $key): array
    {
        return (new Collection($this->getManifest()))
            ->flatMap(fn ($configuration) => (array) ($configuration[$key] ?? []))
            ->filter()
            ->all();
    }

    /**
     * Get the current package manifest.
     * @throws FileNotFoundException
     */
    protected function getManifest(): array
    {
        if (! is_null($this->manifest)) {
            return $this->manifest;
        }

        if (! is_file($this->manifest_path)) {
            $this->build();
        }

        return $this->manifest = is_file($this->manifest_path) ?
            $this->files->getRequire($this->manifest_path) : [];
    }

    /**
     * Build the manifest and write it to disk.
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function build(): void
    {
        $packages = [];

        if ($this->files->exists($path = $this->vendor_path.'/composer/installed.json')) {
            $installed = json_decode($this->files->get($path), true);

            $packages = $installed['packages'] ?? $installed;
        }

        $ignoreAll = in_array('*', $ignore = $this->packagesToIgnore());

        $this->write((new Collection($packages))->mapWithKeys(function ($package) {
            return [$this->format($package['name']) => $package['extra']['laravel'] ?? []];
        })->each(function ($configuration) use (&$ignore) {
            $ignore = array_merge($ignore, $configuration['dont-discover'] ?? []);
        })->reject(function ($configuration, $package) use ($ignore, $ignoreAll) {
            return $ignoreAll || in_array($package, $ignore);
        })->filter()->all());
    }

    /**
     * Format the given package name.
     */
    protected function format(string $package): string
    {
        return str_replace($this->vendor_path.'/', '', $package);
    }

    /**
     * Get every package name that should be ignored.
     */
    protected function packagesToIgnore(): array
    {
        if (! is_file($this->base_path.'/composer.json')) {
            return [];
        }

        return json_decode(file_get_contents(
            $this->base_path.'/composer.json'
        ), true)['extra']['scrapyard-io']['dont-discover'] ?? [];
    }

    /**
     * Write the given manifest array to disk.
     * @throws Exception
     */
    protected function write(array $manifest): void
    {
        if (! is_writable($dirname = dirname($this->manifest_path))) {
            throw new Exception("The {$dirname} directory must be present and writable.");
        }

        $this->files->replace(
            $this->manifest_path, '<?php return '.var_export($manifest, true).';'
        );
    }
}
