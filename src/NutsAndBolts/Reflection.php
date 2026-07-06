<?php

namespace ScrapyardIO\NutsAndBolts;

use FilesystemIterator;
use ReflectionClass;
use ReflectionProperty;
use ReflectionException;
use ReflectionAttribute;
use ReflectionParameter;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Reflection
{
    public static function classes_in_namespace(string $namespace, string $directory): array
    {
        $prefix = rtrim($namespace, '\\') . '\\';
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $classes = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($directory) + 1);
            $class = $prefix . str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relativePath
            );

            if (!class_exists($class)) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * Discovers classes across a directory of sibling Composer packages (e.g. a
     * "microscrap/" or "vendor/microscrap/" folder), reading each package's own
     * composer.json psr-4 map instead of assuming folder names mirror namespaces.
     */
    public static function classes_in_packages_directory(string $directory): array
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $classes = [];

        foreach (glob($directory.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [] as $packageDirectory) {
            foreach (self::psr4_map($packageDirectory) as $namespace => $path) {
                $classes = array_merge(
                    $classes,
                    self::classes_in_namespace($namespace, $packageDirectory.DIRECTORY_SEPARATOR.$path)
                );
            }
        }

        return $classes;
    }

    /**
     * Reads a package's composer.json autoload.psr-4 map ({namespace => relative path}).
     */
    protected static function psr4_map(string $packageDirectory): array
    {
        $composerFile = $packageDirectory.DIRECTORY_SEPARATOR.'composer.json';

        if (! is_file($composerFile)) {
            return [];
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        $psr4 = $composer['autoload']['psr-4'] ?? [];

        $map = [];
        foreach ($psr4 as $namespace => $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            $map[rtrim($namespace, '\\')] = trim($path, '/\\');
        }

        return $map;
    }

    public static function reflect_property(object $object, string $attribute): ?ReflectionProperty
    {
        $reflection = new ReflectionClass($object);
        foreach ($reflection->getProperties() as $property) {
            $result = $property->getAttributes($attribute);
            if ($result) {
                return $property;
            }
        }

        return null;
    }

    /**
     * @throws ReflectionException
     */
    public static function reflect_class(object|string $class, string $attribute): ?ReflectionAttribute
    {
        $attributes = (new ReflectionClass($class))->getAttributes($attribute);

        return $attributes[0] ?? null;
    }

    /**
     * @throws ReflectionException
     */
    public static function reflect_parameter(object|string $class, string $method, string $attribute): ?ReflectionParameter
    {
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethod($method)->getParameters() as $parameter) {
            $result = $parameter->getAttributes($attribute);
            if ($result) {
                return $parameter;
            }
        }

        return null;
    }
}
