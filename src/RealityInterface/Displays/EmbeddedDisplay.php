<?php

namespace RealityInterface\Displays;

use BareMetal\IntegratedCircuit;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;

abstract class EmbeddedDisplay extends IntegratedCircuit
{
    protected FormatSpec $format_spec;

    public function __construct(
        protected readonly int $width,
        protected readonly int $height,
    ) {
        $this->format_spec = $this->generateFormatSpec();
    }

    abstract public function generateFormatSpec(): FormatSpec;

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function getFormatSpec(): FormatSpec
    {
        return $this->format_spec;
    }
}
