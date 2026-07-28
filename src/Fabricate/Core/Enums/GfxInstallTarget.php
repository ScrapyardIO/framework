<?php

namespace Fabricate\Core\Enums;

enum GfxInstallTarget: string
{
    case TUBES = 'tubes';
    case SDL3 = 'sdl3';
    case GLFW = 'glfw';

    public function packageConstraint(): ?string
    {
        return match ($this) {
            self::TUBES => 'scrapyard-io/tubes:^0.6.0',
            self::SDL3 => 'microscrap/sdl3-gfx:^0.6.0',
            self::GLFW => 'microscrap/glfw-gfx:^0.6.0',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::TUBES => 'ScrapyardIO Tubes',
            self::SDL3 => 'SDL3 GFX',
            self::GLFW => 'GLFW GFX',
        };
    }

    public function requiredExtension(): ?string
    {
        return match ($this) {
            self::TUBES => null,
            self::SDL3 => 'sdl3',
            self::GLFW => 'glfw',
        };
    }

    public function extensionLoaded(): bool
    {
        $extension = $this->requiredExtension();

        return is_null($extension) || extension_loaded($extension);
    }

    public function isDesktopBackend(): bool
    {
        return $this === self::SDL3 || $this === self::GLFW;
    }

    public function serviceProvider(): ?string
    {
        return match ($this) {
            self::TUBES => 'ScrapyardIO\\Tubes\\Core\\Providers\\TubesServiceProvider',
            self::SDL3 => null,
            self::GLFW => null,
        };
    }

    /**
     * @return array{driver: string, renderer: string, buffer: string}
     */
    public function windowedDisplayKeys(): array
    {
        return match ($this) {
            self::SDL3 => [
                'driver' => 'sdl3',
                'renderer' => 'sdl3',
                'buffer' => 'sdl3',
            ],
            self::GLFW => [
                'driver' => 'glfw',
                'renderer' => 'glfw',
                'buffer' => 'glfw-ogl',
            ],
            self::TUBES => [
                'driver' => 'color',
                'renderer' => 'phpdafruit',
                'buffer' => 'dirty',
            ],
        };
    }
}
