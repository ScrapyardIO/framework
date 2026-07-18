<?php

namespace Fabricate\Displays;

use DeptOfScrapyardRobotics\Displays\GLFW\GLFWWindow;
use DeptOfScrapyardRobotics\Displays\SDL3\SDL3Window;
use Exception;
use Fabricate\Chassis\EntryNotFoundException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Displays\DisplayException;
use Fabricate\Contracts\Displays\WindowFactory as WindowFactoryContract;
use Fabricate\NutsAndBolts\Manager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class WindowFactoryManager extends Manager implements WindowFactoryContract
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws CircularDependencyException|DisplayException
     * @throws Exception
     */
    public function createSdl3Driver(): WindowedVisualOutput
    {
        if(extension_loaded('sdl3'))
        {
            if (class_exists(SDL3Window::class)) {
                $config = config('displays.windowed.drivers.sdl3', []);

                $width = $config['width'] ?? null;
                $height = $config['height'] ?? null;

                if (is_null($width) || is_null($height)) {
                    throw new DisplayException('Missing Windowed SDL3 Configuration.');
                }

                $scale_factor = (int) ($config['_scale_factor'] ?? 1);
                $title = (string) ($config['title'] ?? 'ScrapyardIO');
                $boot_now = (bool) ($config['boot_now'] ?? true);

                $window = new SDL3Window(
                    (int) $width,
                    (int) $height,
                    $scale_factor,
                    $boot_now,
                    $title,
                );

                return new WindowedVisualOutput($window);
            }

            throw new DisplayException('SDL3 Windowed Displays require the sdl3-displays package. Install it with composer require microscrap/sdl3-displays');

        }

        throw new DisplayException('SDL3 Windowed Displays require the sdl3 extension for PHP. Install it with pie install php-io-extensions/sdl3');

    }

    /**
     * @return WindowedVisualOutput
     * @throws CircularDependencyException
     * @throws ContainerExceptionInterface
     * @throws DisplayException
     * @throws EntryNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function createGlfwDriver(): WindowedVisualOutput
    {
        if (extension_loaded('glfw')) {

            if (class_exists(GLFWWindow::class)) {
                $config = config('displays.windowed.drivers.glfw', []);

                $width = $config['width'] ?? null;
                $height = $config['height'] ?? null;

                if (is_null($width) || is_null($height)) {
                    throw new DisplayException('Missing Windowed GLFW Configuration.');
                }

                $title = (string) ($config['title'] ?? 'ScrapyardIO');
                $boot_now = (bool) ($config['boot_now'] ?? true);

                $window = new GLFWWindow(
                    (int) $width,
                    (int) $height,
                    $boot_now,
                    $title,
                );

                return new WindowedVisualOutput($window);
            }

            throw new DisplayException('GLFW Windowed Displays require the glfw-displays package. Install it with composer require microscrap/glfw-displays');
        }

        throw new DisplayException('GLFW Windowed displays require the glfw extension for PHP. Install it with pie install php-io-extensions/glfw');

    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     */
    public function getDefaultDriver()
    {
        return config('displays.windowed.default', 'sdl3');
    }
}
