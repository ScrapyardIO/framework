<?php

namespace Fabricate\Framebuffers;

use Fabricate\Contracts\Framebuffers\BufferFactory as FactoryContract;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Framebuffers\FramebufferException;
use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\Framebuffers\Strategy\FullFramebuffer;
use Fabricate\Framebuffers\Strategy\PageSegmentBuffer;

class FramebufferManager implements FactoryContract
{
    /**
     * Custom strategy creators registered via {@see extend()}.
     *
     * @var array<string, callable(int, int, FormatSpec): \Fabricate\Framebuffers\Deprecated\Framebuffer>
     */
    protected array $customCreators = [];

    /**
     * Create a framebuffer for the given strategy.
     *
     * @param  non-empty-string  $type
     *
     * @throws FramebufferException
     */
    public function make(string $type, int $width, int $height, ?FormatSpec $formatSpec = null): Framebuffer
    {
        $formatSpec = $this->requireFormatSpec($formatSpec);
        $type = strtolower($type);

        if (isset($this->customCreators[$type])) {
            return ($this->customCreators[$type])($width, $height, $formatSpec);
        }

        return match ($type) {
            'full' => new FullFramebuffer($width, $height, $formatSpec),
            'dirty' => new DirtyRegionsBuffer($width, $height, $formatSpec),
            'page' => new PageSegmentBuffer($width, $height, $formatSpec),
            default => throw new FramebufferException("Framebuffer strategy [{$type}] is not defined."),
        };
    }

    /**
     * @throws FramebufferException
     */
    public function full(int $width, int $height, ?FormatSpec $formatSpec = null): Framebuffer
    {
        return $this->make('full', $width, $height, $formatSpec);
    }

    /**
     * @throws FramebufferException
     */
    public function dirty(int $width, int $height, ?FormatSpec $formatSpec = null): Framebuffer
    {
        return $this->make('dirty', $width, $height, $formatSpec);
    }

    /**
     * @throws FramebufferException
     */
    public function page(int $width, int $height, ?FormatSpec $formatSpec = null): Framebuffer
    {
        return $this->make('page', $width, $height, $formatSpec);
    }

    /**
     * Register a custom framebuffer strategy (e.g. sdl3 from microscrap/sdl3-gfx).
     *
     * @param  non-empty-string  $type
     * @param  callable(int, int, FormatSpec): \Fabricate\Framebuffers\Deprecated\Framebuffer  $callback
     */
    public function extend(string $type, callable $callback): void
    {
        $this->customCreators[strtolower($type)] = $callback;
    }

    /**
     * List every built-in and extended framebuffer strategy.
     *
     * @return array<int, string>
     */
    public function listFramebuffers(): array
    {
        $framebuffers = array_merge(
            ['full', 'dirty', 'page'],
            array_keys($this->customCreators),
        );

        sort($framebuffers);

        return array_values(array_unique($framebuffers));
    }

    /**
     * @throws FramebufferException
     */
    protected function requireFormatSpec(?FormatSpec $formatSpec): FormatSpec
    {
        if (is_null($formatSpec)) {
            throw new FramebufferException('A FormatSpec is required to create a framebuffer.');
        }

        return $formatSpec;
    }
}