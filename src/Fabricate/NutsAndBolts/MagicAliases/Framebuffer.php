<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static \Fabricate\Contracts\Framebuffers\Framebuffer make(string $type, int $width, int $height, \Fabricate\Contracts\Framebuffers\FormatSpec|null $formatSpec = null)
 * @method static \Fabricate\Contracts\Framebuffers\Framebuffer full(int $width, int $height, \Fabricate\Contracts\Framebuffers\FormatSpec|null $formatSpec = null)
 * @method static \Fabricate\Contracts\Framebuffers\Framebuffer dirty(int $width, int $height, \Fabricate\Contracts\Framebuffers\FormatSpec|null $formatSpec = null)
 * @method static \Fabricate\Contracts\Framebuffers\Framebuffer page(int $width, int $height, \Fabricate\Contracts\Framebuffers\FormatSpec|null $formatSpec = null)
 * @method static void extend(string $type, callable $callback)
 *
 * @see \Fabricate\Framebuffers\FramebufferManager
 * @see \Fabricate\Contracts\Framebuffers\Factory
 */
class Framebuffer extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'framebuffer';
    }
}
