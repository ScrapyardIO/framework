<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Framebuffers\FormatSpec;

class WindowedVisualOutput extends VisualOutput
{
    public function __construct(
        public readonly ManagedWindow $window,
    ) {
        parent::__construct($window);
    }

    public function generateFormatSpec(): FormatSpec
    {
        return $this->window->formatSpec();
    }
}
