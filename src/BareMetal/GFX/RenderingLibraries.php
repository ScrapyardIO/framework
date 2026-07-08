<?php

namespace BareMetal\GFX;

use BareMetal\Contracts\GFX\RendererException;
use ScrapyardIO\NutsAndBolts\ScrapyardLibrary;

/**
 * The registry of installed GFX rendering libraries, sibling to
 * {@see \GPIO\Common\GPIO} / {@see \GPIO\Common\GPIOCarriers}.
 *
 * Booting with no config registers the stock renderer packages that are
 * actually installed; static calls resolve a registered name into a renderer
 * instance built from the given constructor arguments.
 *
 * @method static Renderer phpdafruit(mixed ...$arguments)
 * @method static Renderer sdl3(mixed ...$arguments)
 */
class RenderingLibraries extends ScrapyardLibrary
{
    protected static ?self $instance = null;

    /**
     * @var array<string, class-string<Renderer>>
     */
    protected array $renderers = [];

    /**
     * @throws RendererException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $renderer_class = self::resolve($name);

        /** @var Renderer */
        return new $renderer_class(...$arguments);
    }

    /**
     * Resolve a registered renderer name into its class without constructing
     * it — DisplayComponents need the class first to ask for its
     * {@see Renderer2D::preferredFramebuffer()} before building an instance.
     *
     * @return class-string<Renderer>
     *
     * @throws RendererException
     */
    public static function resolve(string $name): string
    {
        $libraries = self::boot();

        $renderer = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
        if (isset($libraries->renderers()[$renderer])) {
            return $libraries->renderers()[$renderer];
        }

        throw RendererException::rendererNotInstalled($name, array_keys($libraries->renderers()));
    }

    /**
     * @return array<string, class-string<Renderer>>
     */
    protected function renderers(): array
    {
        return $this->renderers;
    }

    public function renderer(string $name, string $renderer_class): void
    {
        $this->renderers[$name] = $renderer_class;
    }

    public static function boot(?array $renderers = null): static
    {
        $instance = static::$instance;
        if (is_null($instance)) {
            $self = new self();
            $renderers ??= self::defaultRenderers();

            foreach ($renderers as $name => $renderer_class) {
                $self->renderer($name, $renderer_class);
            }
            $instance = self::$instance = $self;
        }

        return $instance;
    }

    /**
     * The stock renderer packages, registered only when actually installed —
     * same spirit as {@see \GPIO\Common\LoadDefaultFactories::run()}.
     *
     * @return array<string, class-string<Renderer>>
     */
    protected static function defaultRenderers(): array
    {
        $defaults = [
            'phpdafruit' => 'Microscrap\\GFX\\PhpdaFruit\\PhpdafruitGFX',
            'sdl3' => 'Microscrap\\GFX\\SDL3\\Sdl3GFX',
        ];

        return array_filter($defaults, fn (string $renderer_class): bool => class_exists($renderer_class));
    }

    protected static function getInstance(): self
    {
        return self::$instance;
    }
}
