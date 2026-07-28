<?php

namespace Fabricate\Rendering;

use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Rendering\RenderFactory as GFXFactoryContract;
use Fabricate\Contracts\Rendering\RenderingException;
use Fabricate\NutsAndBolts\Manager;
use Psr\Container\ContainerExceptionInterface;

class RenderManager extends Manager implements GFXFactoryContract
{
    public function engine(?string $engine = null): GFXRenderDriver
    {
        return $this->driver($engine);
    }

    /**
     * Create an uncached driver for stateful, presentation-owned rendering.
     */
    public function freshEngine(?string $engine = null): GFXRenderDriver
    {
        $engine ??= $this->getDefaultDriver();

        if (is_null($engine)) {
            throw new RenderingException('Unable to resolve a renderer without an engine name.');
        }

        return $this->createDriver($engine);
    }

    /**
     * List built-in and package-registered renderer engines.
     *
     * @return array<int, string>
     */
    public function listRenderers(): array
    {
        $renderers = array_merge(
            ['phpdafruit'],
            array_keys($this->customCreators),
        );
        sort($renderers);

        return array_values(array_unique($renderers));
    }

    /**
     * @return PhpdafruitGFXRenderDriver
     * @throws RenderingException
     */
    public function createPhpdafruitDriver(): PhpdafruitGFXRenderDriver
    {
        if (class_exists(\Microscrap\GFX\PhpdaFruit\PhpdafruitGfx::class)) {
            return new PhpdafruitGFXRenderDriver;
        }

        throw new RenderingException('The Phpdafruit rendering engine requires the phpdafruit-gfx package. Install it with composer require microscrap/phpdafruit-gfx');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CircularDependencyException
     */
    public function getDefaultDriver(): ?string
    {
        return config('gfx.rendering.default', 'phpdafruit');
    }
}
