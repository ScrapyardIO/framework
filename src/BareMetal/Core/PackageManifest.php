<?php

namespace BareMetal\Core;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Env;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;

class PackageManifest
{
    /**
     * The vendor path.
     */
    public string $vendor_path;

    /**
     * The loaded manifest array.
     */
    public array $manifest = [];

    public function __construct(
        public Filesystem $files,
        public string $base_path,
        public ?string $manifest_path
    ) {
        $this->vendor_path = Env::get('COMPOSER_VENDOR_DIR') ?: $base_path.'/vendor';
    }

    /**
     * Get every value for all packages for the given configuration name.
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
     */
    public function build(): void
    {
        $packages = [];

        if ($this->files->exists($path = $this->vendor_path.'/composer/installed.json')) {
            $installed = json_decode($this->files->get($path), true);

            $packages = $installed['packages'] ?? $installed;
        }

        $ignore_all = in_array('*', $ignore = $this->packagesToIgnore());

        $this->write((new Collection($packages))->mapWithKeys(function ($package) {
            return [$this->format($package['name']) => $package['extra']['scrapyard-io'] ?? []];
        })->each(function ($configuration) use (&$ignore) {
            $ignore = array_merge($ignore, $configuration['dont-discover'] ?? []);
        })->reject(function ($configuration, $package) use ($ignore, $ignore_all) {
            return $ignore_all || in_array($package, $ignore);
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
     * Get every service provider class name for all packages.
     *
     * @return array
     */
    public function providers(): array
    {
        return $this->config('providers');
    }

    /**
     * Get every alias for all packages.
     */
    public function aliases(): array
    {
        return $this->config('aliases');
    }


}


