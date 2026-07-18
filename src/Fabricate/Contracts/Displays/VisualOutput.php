<?php

namespace Fabricate\Contracts\Displays;

use Fabricate\Contracts\Framebuffers\FormatSpec;

interface VisualOutput
{
    public function width(): int;
    public function height(): int;
    public function formatSpec(): FormatSpec;
    public function generateFormatSpec(): FormatSpec;
}
