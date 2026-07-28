<?php

namespace Fabricate\Sketches;

use Fabricate\Contracts\Chassis\WireframeServiceContainer;
use Fabricate\Contracts\Sketches\Attributes\Sketch as SketchAttribute;
use Fabricate\Contracts\Sketches\Sketch as SketchContract;
use Fabricate\Contracts\Sketches\SketchException;
use Fabricate\Contracts\Sketches\SketchRegistry as SketchRegistryContract;
use Fabricate\NutsAndBolts\Str;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;

class SketchRegistry implements SketchRegistryContract
{
    /**
     * @var array<string, class-string>
     */
    protected array $sketches = [];

    public function __construct(
        protected WireframeServiceContainer $container,
    ) {}

    /**
     * Register an attributed Sketch class (package or config-loaded).
     *
     * @param  class-string  $class
     *
     * @throws SketchException
     */
    public function register(string $class): void
    {
        $reflection = $this->reflectSketchClass($class);
        $attribute = $this->requireSketchAttribute($reflection);
        $name = trim($attribute->name);

        if ($name === '') {
            throw new SketchException("Sketch [{$class}] attribute name must not be empty.");
        }

        $this->bind($name, $class);
    }

    /**
     * Register a conventionally discovered app Sketch under an explicit name.
     *
     * @param  class-string  $class
     *
     * @throws SketchException
     */
    public function registerConvention(string $name, string $class): void
    {
        $this->reflectSketchClass($class);
        $this->bind($name, $class);
    }

    /**
     * Resolve a registered Sketch through the container.
     *
     * @throws SketchException
     */
    public function resolve(string $name): SketchContract
    {
        $normalized = $this->normalize($name);

        if (! isset($this->sketches[$normalized])) {
            throw new SketchException("Sketch [{$normalized}] is not registered.");
        }

        $sketch = $this->container->make($this->sketches[$normalized]);

        if (! $sketch instanceof SketchContract) {
            throw new SketchException(
                'Resolved class ['.$this->sketches[$normalized].'] must implement '.SketchContract::class.'.'
            );
        }

        return $sketch;
    }

    /**
     * Determine whether a sketch name is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->sketches[$this->normalize($name)]);
    }

    /**
     * @return array<string, class-string>
     */
    public function all(): array
    {
        return $this->sketches;
    }

    /**
     * @param  class-string  $class
     *
     * @throws SketchException
     */
    protected function bind(string $name, string $class): void
    {
        $normalized = $this->normalize($name);

        if ($normalized === '') {
            throw new SketchException("Sketch name for [{$class}] must not be empty.");
        }

        if (isset($this->sketches[$normalized])) {
            throw new SketchException(
                "Sketch [{$normalized}] is already registered as [{$this->sketches[$normalized]}]."
            );
        }

        $this->sketches[$normalized] = $class;
    }

    /**
     * @param  class-string  $class
     * @return ReflectionClass<object>
     *
     * @throws SketchException
     */
    protected function reflectSketchClass(string $class): ReflectionClass
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new SketchException("Sketch class [{$class}] does not exist.", previous: $e);
        }

        if (! $reflection->implementsInterface(SketchContract::class)) {
            throw new SketchException(
                "Class [{$class}] must implement ".SketchContract::class.'.'
            );
        }

        if (! $reflection->isInstantiable()) {
            throw new SketchException("Sketch class [{$class}] must be instantiable.");
        }

        return $reflection;
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     *
     * @throws SketchException
     */
    protected function requireSketchAttribute(ReflectionClass $reflection): SketchAttribute
    {
        $attributes = $reflection->getAttributes(SketchAttribute::class, ReflectionAttribute::IS_INSTANCEOF);

        if ($attributes === []) {
            throw new SketchException(
                'Sketch ['.$reflection->getName().'] must declare the #['.SketchAttribute::class.'] attribute.'
            );
        }

        return $attributes[0]->newInstance();
    }

    protected function normalize(string $name): string
    {
        return Str::lower(trim($name));
    }
}
