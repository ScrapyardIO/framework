<?php

namespace RealityInterface\Displays\Applied\ePaper;

use RealityInterface\Displays\Attributes\OutputsFourColors;
use RealityInterface\Displays\Contracts\Applied\ePaper\QuadColorEInkDisplay;
use RealityInterface\Displays\EmbeddedDisplay;
use RealityInterface\Displays\Exceptions\DisplayException;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;

class QuadColorePaperDisplay extends ElectronicInkDisplay
{
    public function transmit(DumpedBuffer $buffer): void
    {
        $panel = $this->display;

        if (! $panel instanceof QuadColorEInkDisplay) {
            throw DisplayException::cannotTransmitTo($panel::class, QuadColorEInkDisplay::class);
        }

        $panel->display($buffer);
    }

    public static function as(EmbeddedDisplay $circuit): static
    {
        $attr = reflect_class($circuit, OutputsFourColors::class);
        if ($attr->getName() == OutputsFourColors::class) {
            return new static($circuit);
        }

        throw DisplayException::missingRequiredAbility('QuadColorePaperDisplay', $circuit::class, 'OutputsFourColors');
    }
}
