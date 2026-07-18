<?php

namespace Fabricate\Gfx;

use Fabricate\Chassis\EntryNotFoundException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Gfx\GfxException;
use Fabricate\NutsAndBolts\Manager;
use Fabricate\Contracts\Gfx\Factory as GfxFactoryContract;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class RenderManager extends Manager implements GfxFactoryContract
{
    public function engine(?string $engine = null): GFXRenderDriver
    {
        return $this->driver($engine);
    }

    /**
     * @return SDL3GfxRenderDriver
     * @throws GfxException
     */
    public function createSdl3Driver(): SDL3GfxRenderDriver
    {
        if (extension_loaded('sdl3')) {

            if (class_exists(\Microscrap\GFX\SDL3\SDL3Gfx::class)) {
                return new SDL3GfxRenderDriver;
            }

            throw new GfxException('The SDL3 rendering engine requires the sdl3-gfx package. Install it with composer require microscrap/sdl3-gfx');
        }

        throw new GfxException('The SDL3 rendering engine requires the sdl3 extension for PHP. Install it with pie install php-io-extensions/sdl3');
    }

    /**
     * @return GlfwGfxRenderDriver
     * @throws GfxException
     */
    public function createGlfwDriver(): GlfwGfxRenderDriver
    {
        if (extension_loaded('glfw')) {

            if (class_exists(\Microscrap\GFX\GLFW\GLFWGfx::class)) {
                return new GlfwGfxRenderDriver;
            }

            throw new GfxException('The GLFW rendering engine requires the glfw-gfx package. Install it with composer require microscrap/glfw-gfx');
        }

        throw new GfxException('The GLFW rendering engine requires the glfw extension for PHP. Install it with pie install php-io-extensions/glfw');
    }

    /**
     * @return PhpdafruitGfxRenderDriver
     * @throws GfxException
     */
    public function createPhpdafruitDriver(): PhpdafruitGfxRenderDriver
    {
        if (class_exists(\Microscrap\GFX\PhpdaFruit\PhpdafruitGfx::class)) {
            return new PhpdafruitGfxRenderDriver;
        }

        throw new GfxException('The Phpdafruit rendering engine requires the phpdafruit-gfx package. Install it with composer require microscrap/phpdafruit-gfx');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     */
    public function getDefaultDriver()
    {
        return config('gfx.rendering.default', 'phpdafruit');
    }
}
