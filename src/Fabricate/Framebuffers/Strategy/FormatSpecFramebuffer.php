<?php

namespace Fabricate\Framebuffers\Strategy;

use Fabricate\Contracts\Framebuffers\FormatSpecFramebufferFactory as FactoryContract;
use Fabricate\Contracts\Framebuffers\FramebufferException;
use Fabricate\Contracts\Framebuffers\SoftwareRenderableFramebuffer;
use Fabricate\Contracts\Displays\Display;
use Fabricate\Contracts\Rendering\GFXRenderer;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\Packers\PixelPackers;

abstract class FormatSpecFramebuffer extends FramebufferWithGrid implements SoftwareRenderableFramebuffer
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

    public function supportsDisplay(Display $display): bool
    {
        return true;
    }

    public function supportsRenderer(GFXRenderer $renderer): bool
    {
        return true;
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
