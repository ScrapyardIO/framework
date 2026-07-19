<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Framebuffers\FormatSpec;

class EmbeddedVisualOutput extends VisualOutput
{
    public function __construct(
        public readonly EmbeddedDisplay $circuit,
    ) {
        parent::__construct($circuit);
    }

    public function generateFormatSpec(): FormatSpec
    {
        return $this->circuit->formatSpec();
    }
}
