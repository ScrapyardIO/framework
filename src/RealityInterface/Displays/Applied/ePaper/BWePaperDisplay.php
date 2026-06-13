<?php

namespace RealityInterface\Displays\Applied\ePaper;

use RealityInterface\Displays\Attributes\OutputsOnlyBlackAndWhite;
use RealityInterface\Displays\Contracts\Applied\ePaper\BlackAndWhiteEInkDisplay;
use RealityInterface\Displays\EmbeddedDisplay;
use RealityInterface\Displays\Exceptions\DisplayException;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;

class BWePaperDisplay extends ElectronicInkDisplay
{
    public function transmit(DumpedBuffer $buffer): void
    {
        $panel = $this->display;

        if (! $panel instanceof BlackAndWhiteEInkDisplay) {
            throw DisplayException::cannotTransmitTo($panel::class, BlackAndWhiteEInkDisplay::class);
        }

        $panel->display($buffer);
    }

    public static function as(EmbeddedDisplay $circuit): static
    {
        $attr = reflect_class($circuit, OutputsOnlyBlackAndWhite::class);
        if ($attr->getName() == OutputsOnlyBlackAndWhite::class) {
            return new static($circuit);
        }

        throw DisplayException::missingRequiredAbility('BWePaperDisplay', $circuit::class, 'OutputsOnlyBlackAndWhite');
    }
}
