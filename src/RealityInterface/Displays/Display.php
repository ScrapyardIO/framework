<?php

namespace RealityInterface\Displays;

use RealityInterface\Displays\Repositories\EmbeddedDisplayRepository;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;

abstract class Display
{
    public function __construct(
        protected EmbeddedDisplay $display,
    ) {}

    abstract public function transmit(DumpedBuffer $buffer): void;

    abstract public static function as(EmbeddedDisplay $circuit): static;

    public function height(): int
    {
        return $this->display->height();
    }

    public function width(): int
    {
        return $this->display->width();
    }

    public function getFormatSpec(): FormatSpec
    {
        return $this->display->getFormatSpec();
    }

    public function embeddedDisplay(): EmbeddedDisplay
    {
        return $this->display;
    }

    public static function using(string $circuit_name): static
    {
        $circuit = EmbeddedDisplayRepository::display($circuit_name);

        return static::as($circuit);
    }
}
