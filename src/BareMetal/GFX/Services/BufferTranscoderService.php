<?php

namespace BareMetal\GFX\Services;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\GFX\BufferEncoder;
use BareMetal\Contracts\GFX\TranscoderException;
use BareMetal\GFX\Services\Encoders\MonoToRgbaEncoder;
use BareMetal\GFX\Services\Encoders\PaletteMonoToRgbaEncoder;
use BareMetal\GFX\Services\Encoders\Rgb565ToRgbaEncoder;

/**
 * Converts renderer dumps into the FormatSpec a display expects.
 *
 * A DisplayComponent builds one of these against its IC's spec and pushes
 * every DumpedBuffer through transcode(): dumps whose metadata already
 * matches the display spec pass through untouched (the no-transcode fast
 * path), anything else is handed to the first registered encoder that
 * supports the conversion. Downstream packages register extra encoders like
 * {@see \BareMetal\Framebuffers\Packers\PixelPackers::register()} — custom
 * registrations are consulted before the built-ins, so they can also
 * override a default.
 */
class BufferTranscoderService
{
    /**
     * Custom encoder registrations, newest first.
     *
     * @var array<int, class-string<BufferEncoder>>
     */
    protected static array $registered = [];

    public function __construct(
        protected FormatSpec $display_spec
    ) {}

    /**
     * @param  class-string<BufferEncoder>  $encoder_class
     *
     * @throws TranscoderException
     */
    public static function register(string $encoder_class): void
    {
        if (! is_subclass_of($encoder_class, BufferEncoder::class)) {
            throw TranscoderException::notAnEncoder($encoder_class);
        }

        array_unshift(static::$registered, $encoder_class);
    }

    /**
     * Drop all custom registrations, restoring the built-in defaults.
     */
    public static function reset(): void
    {
        static::$registered = [];
    }

    /**
     * @throws TranscoderException
     */
    public function transcode(DumpedBuffer $dump): DumpedBuffer
    {
        if ($this->formatSpecsAreTheSame($dump->metadata)) {
            return $dump;
        }

        return $this->resolveEncoder($dump->metadata)->encode($dump, $this->display_spec);
    }

    protected function formatSpecsAreTheSame(FormatSpec $spec): bool
    {
        // Value comparison: specs are mutable at runtime on the display side
        // (e.g. SSD1306 addressing-mode changes regenerate them), so equality
        // is decided per dump, never cached.
        return $this->display_spec == $spec;
    }

    /**
     * @throws TranscoderException
     */
    protected function resolveEncoder(FormatSpec $source): BufferEncoder
    {
        $encoder_classes = [...static::$registered, ...self::defaultEncoders()];

        foreach ($encoder_classes as $encoder_class) {
            /** @var BufferEncoder $encoder */
            $encoder = new $encoder_class;

            if ($encoder->supports($source, $this->display_spec)) {
                return $encoder;
            }
        }

        throw TranscoderException::unsupportedConversion($source, $this->display_spec);
    }

    /**
     * @return array<int, class-string<BufferEncoder>>
     */
    protected static function defaultEncoders(): array
    {
        return [
            PaletteMonoToRgbaEncoder::class,
            MonoToRgbaEncoder::class,
            Rgb565ToRgbaEncoder::class,
        ];
    }
}
