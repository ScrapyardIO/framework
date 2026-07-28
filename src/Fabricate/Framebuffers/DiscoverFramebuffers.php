<?php

namespace Fabricate\Framebuffers;

use Fabricate\Contracts\Framebuffers\Attributes\AsFramebuffer;
use Fabricate\NutsAndBolts\Str;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DiscoverFramebuffers
{
    /**
     * Discover app framebuffer classes that declare #[AsFramebuffer].
     *
     * @return array<string, class-string>  registration key => FQCN
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

            $attributes = $class->getAttributes(AsFramebuffer::class, ReflectionAttribute::IS_INSTANCEOF);

            if ($attributes === []) {
                continue;
            }

            /** @var AsFramebuffer $attribute */
            $attribute = $attributes[0]->newInstance();
            $name = trim($attribute->name);

            if ($name === '') {
                continue;
            }

            $discovered[strtolower($name)] = $class->getName();
        }

        return $discovered;
    }

    /**
     * @return class-string
     */
    protected static function classFromFile(SplFileInfo $file, string $basePath): string
    {
        $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return ucfirst(Str::camel(str_replace(
            [DIRECTORY_SEPARATOR, ucfirst(basename(app()->path())).'\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        )));
    }
}
