<?php

namespace BareMetal\Framebuffers\Packers;

use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\FramebufferException;
use BareMetal\Contracts\Framebuffers\PixelPacker;

/**
 * Resolves the PixelPacker for a PixelFormat.
 *
 * Ships with the built-in packers pre-wired; downstream modules (renderers,
 * panel drivers) can register their own packer per format — including formats
 * with no built-in, like PLANAR — without touching any buffer class.
 */
final class PixelPackers
{
    /**
     * Custom registrations, keyed by PixelFormat value. Checked before the
     * built-in defaults so a registration can also override a default.
     *
     * @var array<string, class-string<PixelPacker>>
     */
    protected static array $registered = [];

    /**
     * @param  class-string<PixelPacker>  $packer_class
     *
     * @throws FramebufferException
     */
    public static function register(PixelFormat $format, string $packer_class): void
    {
        if (! is_subclass_of($packer_class, PixelPacker::class)) {
            throw new FramebufferException("{$packer_class} does not implement " . PixelPacker::class . '.');
        }

        static::$registered[$format->value] = $packer_class;
    }

    /**
     * @throws FramebufferException
     */
    public static function resolve(PixelFormat $format): PixelPacker
    {
        $packer_class = static::$registered[$format->value] ?? static::defaultPacker($format);

        if (is_null($packer_class)) {
            throw new FramebufferException("No packer registered for pixel format '{$format->value}'.");
        }

        return new $packer_class;
    }

    /**
     * Drop all custom registrations, restoring the built-in defaults.
     */
    public static function reset(): void
    {
        static::$registered = [];
    }

    /**
     * @return class-string<PixelPacker>|null
     */
    protected static function defaultPacker(PixelFormat $format): ?string
    {
        return match ($format) {
            PixelFormat::ROW_MAJOR => RowMajorPacker::class,
            PixelFormat::MONO_VERTICAL_PAGE => VerticalPagePacker::class,
            PixelFormat::MONO_HORIZONTAL => MonoHorizontalPacker::class,
            default => null,
        };
    }
}
