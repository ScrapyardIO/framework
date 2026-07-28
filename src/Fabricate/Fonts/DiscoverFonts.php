<?php

namespace Fabricate\Fonts;

use Fabricate\NutsAndBolts\Str;
use Fabricate\Rendering\Fonts\GFXFont;
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DiscoverFonts
{
    /**
     * Discover concrete GFXFont subclasses under the given path.
     *
     * @return array<string, class-string<GFXFont>>  registration key => FQCN
     */
    public static function within(string $path, string $basePath): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $discovered = [];

        foreach (Finder::create()->files()->name('*.php')->in($path) as $file) {
            try {
                $class = new ReflectionClass(static::classFromFile($file, $basePath));
            } catch (ReflectionException) {
                continue;
            }

            if (! $class->isInstantiable()) {
                continue;
            }

            if (! $class->isSubclassOf(GFXFont::class)) {
                continue;
            }

            $name = Str::kebab($class->getShortName());

            if ($name === '') {
                continue;
            }

            $discovered[Str::lower($name)] = $class->getName();
        }

        return $discovered;
    }

    /**
     * @return class-string
     */
    protected static function classFromFile(SplFileInfo $file, string $basePath): string
    {
        $basePath = realpath($basePath) ?: $basePath;
        $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return ucfirst(Str::camel(str_replace(
            [DIRECTORY_SEPARATOR, ucfirst(basename(app()->path())).'\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        )));
    }
}
