<?php

namespace RealityInterface\Displays\Applied\ePaper;

use RealityInterface\Displays\Attributes\OutputsThreeColors;
use RealityInterface\Displays\Contracts\Applied\ePaper\TriColorEInkDisplay;
use RealityInterface\Displays\EmbeddedDisplay;
use RealityInterface\Displays\Exceptions\DisplayException;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;

class TriColorePaperDisplay extends ElectronicInkDisplay
{
    public function transmit(DumpedBuffer $buffer): void
    {
        $panel = $this->display;

        if (! $panel instanceof TriColorEInkDisplay) {
            throw DisplayException::cannotTransmitTo($panel::class, TriColorEInkDisplay::class);
        }

        $panel->display($buffer);
    }

    public static function as(EmbeddedDisplay $circuit): static
    {
        $attr = reflect_class($circuit, OutputsThreeColors::class);
        if ($attr->getName() == OutputsThreeColors::class) {
            return new static($circuit);
        }

        throw DisplayException::missingRequiredAbility('TriColorePaperDisplay', $circuit::class, 'OutputsThreeColors');
    }
}
