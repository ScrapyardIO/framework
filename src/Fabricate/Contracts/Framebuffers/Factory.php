<?php

namespace Fabricate\Contracts\Framebuffers;

interface Factory
{
    /**
     * Create a framebuffer for the given strategy.
     *
     * Core strategies: full, dirty, page. Packages may register more via {@see extend()}.
     *
     * @param non-empty-string $type
     * @param int $width
     * @param int $height
     * @param FormatSpec|null $formatSpec
     * @return Framebuffer
     */
    public function make(string $type, int $width, int $height, ?FormatSpec $formatSpec = null): Framebuffer;

    /**
     * @param int $width
     * @param int $height
     * @param FormatSpec|null $formatSpec
     * @return Framebuffer
     */
    public function full(int $width, int $height, ?FormatSpec $formatSpec = null): Framebuffer;

    /**
     * @param int $width
     * @param int $height
     * @param FormatSpec|null $formatSpec
     * @return Framebuffer
     */
    public function dirty(int $width, int $height, ?FormatSpec $formatSpec = null): Framebuffer;

    /**
     * Page-segment / monochrome paged strategy (e.g. SSD1306).
     *
     * @param int $width
     * @param int $height
     * @param FormatSpec|null $formatSpec
     * @return Framebuffer
     */
    public function page(int $width, int $height, ?FormatSpec $formatSpec = null): Framebuffer;

    /**
     * Register a custom framebuffer strategy (e.g. sdl3 from microscrap/sdl3-gfx).
     *
     * @param  non-empty-string  $type
     * @param  callable(int, int, FormatSpec): Framebuffer  $callback
     */
    public function extend(string $type, callable $callback): void;
}
