<?php

namespace Fabricate\Displays;

use DeptOfScrapyardRobotics\Displays\GLFW\GLFWWindow;
use DeptOfScrapyardRobotics\Displays\SDL3\SDL3Window;
use Fabricate\Contracts\Displays\DisplayComponent as DisplayComponentInterface;
use Fabricate\Contracts\Displays\DisplayException;
use Fabricate\NutsAndBolts\MagicAliases\Framebuffer;
use Fabricate\NutsAndBolts\MagicAliases\Rendering;
use Fabricate\NutsAndBolts\MagicAliases\Window;
use Microscrap\GFX\GLFW\GLFWGfx;
use Microscrap\GFX\GLFW\GLFWOpenGLFramebuffer;
use Microscrap\GFX\SDL3\Sdl3Framebuffer;

class WindowedDisplayFactory extends DisplayFactory
{
    public ?string $render_driver = null;

    public ?string $framebuffer = null;

    public function __construct(
        public string $windowed_driver
    ) {}

    public function renderedUsing(string $driver): static
    {
        $this->render_driver = $driver;

        return $this;
    }

    public function withFramebuffer(string $driver): static
    {
        $this->framebuffer = $driver;

        return $this;
    }

    public function create(): DisplayComponentInterface
    {
        if (is_null($this->render_driver)) {
            throw new DisplayException('Call renderedUsing() before create().');
        }

        /** @var WindowedVisualOutput $output */
        $output = Window::driver($this->windowed_driver);
        $window = $output->window;
        $framebuffer_driver = $this->framebuffer ?? $this->defaultFramebufferDriver();

        $this->assertCompatibleStack($window, $this->render_driver, $framebuffer_driver);

        if ($framebuffer_driver === 'sdl3' && $window instanceof SDL3Window) {
            $sdl_renderer = $window->renderer();

            if (is_null($sdl_renderer)) {
                throw new DisplayException('SDL3Window has no renderer — boot the window first.');
            }

            $framebuffer = Sdl3Framebuffer::attachedTo(
                $sdl_renderer,
                $output->formatSpec(),
                $output->width(),
                $output->height(),
            );
        } elseif ($framebuffer_driver === 'glfw-ogl' && $window instanceof GLFWWindow) {
            $native = $window->nativeWindow();

            if (is_null($native)) {
                throw new DisplayException('GLFWWindow has no native handle — boot the window first.');
            }

            $framebuffer = GLFWOpenGLFramebuffer::attachedTo(
                $native,
                $output->formatSpec(),
                $output->width(),
                $output->height(),
            );
        } else {
            $framebuffer = Framebuffer::make(
                $framebuffer_driver,
                $output->width(),
                $output->height(),
                $output->formatSpec(),
            );
        }

        $gfx = Rendering::engine($this->render_driver)->renderer;

        if ($gfx instanceof GLFWGfx && $window instanceof GLFWWindow) {
            $native = $window->nativeWindow();

            if (is_null($native)) {
                throw new DisplayException('GLFWWindow has no native handle — boot the window first.');
            }

            $gfx->useNativeWindow($native, $output->width(), $output->height());
        }

        return new DisplayComponent($output, $framebuffer, $gfx);
    }

    protected function defaultFramebufferDriver(): string
    {
        return match ($this->render_driver) {
            'glfw' => 'glfw-ogl',
            'sdl3' => 'sdl3',
            default => $this->render_driver,
        };
    }

    /**
     * @throws DisplayException
     */
    protected function assertCompatibleStack(object $window, string $render_driver, string $framebuffer_driver): void
    {
        if ($window instanceof GLFWWindow) {
            if ($render_driver === 'sdl3') {
                throw new DisplayException(
                    "Incompatible stack: windowed('glfw') cannot use renderedUsing('sdl3') — SDL3Gfx needs an SDL renderer. Use renderedUsing('glfw')."
                );
            }

            if ($framebuffer_driver === 'sdl3') {
                throw new DisplayException(
                    "Incompatible stack: windowed('glfw') cannot use withFramebuffer('sdl3'). Use withFramebuffer('glfw-ogl') or omit it."
                );
            }
        }

        if ($window instanceof SDL3Window) {
            if ($render_driver === 'glfw') {
                throw new DisplayException(
                    "Incompatible stack: windowed('sdl3') cannot use renderedUsing('glfw') — GLFWGfx needs a GLFW context. Use renderedUsing('sdl3')."
                );
            }

            if ($framebuffer_driver === 'glfw-ogl') {
                throw new DisplayException(
                    "Incompatible stack: windowed('sdl3') cannot use withFramebuffer('glfw-ogl'). Use withFramebuffer('sdl3') or omit it."
                );
            }
        }
    }
}
