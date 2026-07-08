<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Displays;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Displays\Display;

/**
 * A Display IC double that skips all transport wiring and simply records
 * every frame handed to transmit(), so tests can assert on exactly what a
 * DisplayComponent pushed out (and whether it was transcoded on the way).
 */
class FakeDisplay extends Display
{
    /**
     * @var array<int, DumpedBuffer>
     */
    public array $transmitted = [];

    public function __construct(
        int $width,
        int $height,
        protected FormatSpec $spec_to_generate,
    ) {
        parent::__construct($width, $height);
    }

    public function generateFormatSpec(): FormatSpec
    {
        return $this->spec_to_generate;
    }

    public function transmit(DumpedBuffer $frame): void
    {
        $this->transmitted[] = $frame;
    }
}
