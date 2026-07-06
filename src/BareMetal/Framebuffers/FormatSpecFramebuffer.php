<?php

namespace BareMetal\Framebuffers;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\FormatSpecFramebufferFactory as FactoryContract;
use BareMetal\Contracts\Framebuffers\FramebufferException;
use BareMetal\Framebuffers\Packers\PixelPackers;

abstract class FormatSpecFramebuffer extends Framebuffer
{
    protected static string $factory_class;

    public function __construct(
        int $width,
        int $height,
        protected FormatSpec $format_spec,
    ) {
        parent::__construct($width, $height);
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    abstract public function dump(): array;

    public function formatSpec(): FormatSpec
    {
        return $this->format_spec;
    }

    /**
     * Shape the raw logical grid into the layout this buffer's FormatSpec
     * advertises, so every DumpedBuffer carries data already in its declared
     * format and downstream can trust the metadata without re-inspecting it.
     *
     * @return array<int, int>
     *
     * @throws FramebufferException
     */
    protected function formatRawDump(): array
    {
        return PixelPackers::resolve($this->format_spec->pixel_format)
            ->pack($this->rawDump(), $this->format_spec, $this->width, $this->height);
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function flush(): array
    {
        $data = $this->dump();

        $this->grid->clear();

        return $data;
    }

    /**
     * @throws FramebufferException
     */
    public static function size(int $width, int $height): FactoryContract
    {
        if (! isset(static::$factory_class)) {
            throw new FramebufferException('Factory class must be set on ' . static::class . '.');
        }

        $factory_class = static::$factory_class;

        return new $factory_class($width, $height);
    }
}
