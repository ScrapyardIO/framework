<?php

namespace BareMetal\Contracts\GFX;

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use ScrapyardIO\NutsAndBolts\ScrapyardIOException;

class TranscoderException extends ScrapyardIOException
{
    public static function unsupportedConversion(FormatSpec $source, FormatSpec $target): static
    {
        return new static(sprintf(
            "No encoder can convert '%s' B%d buffers into '%s' B%d.",
            $source->pixel_format->value,
            $source->bit_depth->value,
            $target->pixel_format->value,
            $target->bit_depth->value,
        ));
    }

    public static function notAnEncoder(string $encoder_class): static
    {
        return new static("{$encoder_class} does not implement " . BufferEncoder::class . '.');
    }

    public static function missingDimensions(): static
    {
        return new static('The DumpedBuffer carries no width/height, so its pixels cannot be decoded.');
    }
}
