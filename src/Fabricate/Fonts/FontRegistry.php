<?php

namespace Fabricate\Fonts;

use Fabricate\Contracts\Fonts\FontException;
use Fabricate\Contracts\Fonts\FontRegistry as RegistryContract;
use Fabricate\Rendering\Fonts\GFXFont;
use ReflectionClass;
use ReflectionException;

class FontRegistry implements RegistryContract
{
    /**
     * @var array<string, class-string<GFXFont>>
     */
    protected array $fonts = [];

    /**
     * Lazily instantiated font instances (bitmap data is heavy).
     *
     * @var array<string, GFXFont>
     */
    protected array $instances = [];

    /**
     * @param  class-string<GFXFont>  $class_name
     *
     * @throws FontException
     */
    public function addFont(string $name, string $class_name): void
    {
        if (! $this->validateClassImplementation($class_name)) {
            throw new FontException("Font [{$class_name}] must be a concrete subclass of ".GFXFont::class.'.');
        }

        $this->fonts[$name] = $class_name;
        unset($this->instances[$name]);
    }

    /**
     * @throws FontException
     */
    public function font(string $name): GFXFont
    {
        if (! isset($this->fonts[$name])) {
            throw new FontException("Font [{$name}] not registered.");
        }

        if (! isset($this->instances[$name])) {
            $class = $this->fonts[$name];
            $this->instances[$name] = new $class;
        }

        return $this->instances[$name];
    }

    public function hasFont(string $name): bool
    {
        return isset($this->fonts[$name]);
    }

    /**
     * @return array<string, class-string<GFXFont>>
     */
    public function listFonts(): array
    {
        return $this->fonts;
    }

    protected function validateClassImplementation(string $class_name): bool
    {
        try {
            $reflection = new ReflectionClass($class_name);
        } catch (ReflectionException) {
            return false;
        }

        return $reflection->isInstantiable()
            && $reflection->isSubclassOf(GFXFont::class);
    }
}
