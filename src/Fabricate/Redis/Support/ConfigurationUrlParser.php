<?php

namespace Fabricate\Redis\Support;

use Fabricate\NutsAndBolts\Arr;
use InvalidArgumentException;

class ConfigurationUrlParser
{
    /**
     * @var array<string, string>
     */
    protected static array $driverAliases = [
        'mssql' => 'sqlsrv',
        'mysql2' => 'mysql',
        'postgres' => 'pgsql',
        'postgresql' => 'pgsql',
        'sqlite3' => 'sqlite',
        'redis' => 'tcp',
        'rediss' => 'tls',
    ];

    /**
     * @param  array<string, mixed>|string  $config
     * @return array<string, mixed>
     */
    public function parseConfiguration($config): array
    {
        if (is_string($config)) {
            $config = ['url' => $config];
        }

        $url = Arr::pull($config, 'url');

        if (! $url) {
            return $config;
        }

        $rawComponents = $this->parseUrl($url);

        $decodedComponents = $this->parseStringsToNativeTypes(
            array_map(rawurldecode(...), $rawComponents)
        );

        return array_merge(
            $config,
            $this->getPrimaryOptions($decodedComponents),
            $this->getQueryOptions($rawComponents)
        );
    }

    /**
     * @param  array<string, mixed>  $url
     * @return array<string, mixed>
     */
    protected function getPrimaryOptions($url): array
    {
        return array_filter([
            'driver' => $this->getDriver($url),
            'database' => $this->getDatabase($url),
            'host' => $url['host'] ?? null,
            'port' => $url['port'] ?? null,
            'username' => $url['user'] ?? null,
            'password' => $url['pass'] ?? null,
        ], fn ($value) => ! is_null($value));
    }

    /**
     * @param  array<string, mixed>  $url
     */
    protected function getDriver($url): ?string
    {
        $alias = $url['scheme'] ?? null;

        if (is_null($alias)) {
            return null;
        }

        return static::$driverAliases[$alias] ?? $alias;
    }

    /**
     * @param  array<string, mixed>  $url
     */
    protected function getDatabase($url): ?string
    {
        $path = $url['path'] ?? null;

        return $path && $path !== '/' ? substr($path, 1) : null;
    }

    /**
     * @param  array<string, mixed>  $url
     * @return array<string, mixed>
     */
    protected function getQueryOptions($url): array
    {
        $queryString = $url['query'] ?? null;

        if (is_null($queryString)) {
            return [];
        }

        $query = [];

        parse_str($queryString, $query);

        return $this->parseStringsToNativeTypes($query);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseUrl(string $url): array
    {
        $url = preg_replace('#^(sqlite3?):///#', '$1://null/', $url);

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            throw new InvalidArgumentException('The database configuration URL is malformed.');
        }

        return $parsedUrl;
    }

    protected function parseStringsToNativeTypes(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map($this->parseStringsToNativeTypes(...), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $parsedValue = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $parsedValue;
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    public static function getDriverAliases(): array
    {
        return static::$driverAliases;
    }

    public static function addDriverAlias(string $alias, string $driver): void
    {
        static::$driverAliases[$alias] = $driver;
    }
}
