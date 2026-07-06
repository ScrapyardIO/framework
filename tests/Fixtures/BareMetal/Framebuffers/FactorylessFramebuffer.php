<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Framebuffers;

use BareMetal\Framebuffers\FormatSpecFramebuffer;

/**
 * A FormatSpecFramebuffer that never declares its factory class, for
 * asserting the size() guard path.
 */
class FactorylessFramebuffer extends FormatSpecFramebuffer
{
    public function dump(): array
    {
        return [];
    }
}
