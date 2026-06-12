<?php

namespace RealityInterface\Displays\Applied\FullColorTFT;

use RealityInterface\Displays\Attributes\OutputsColor;
use RealityInterface\Displays\Contracts\Applied\FullColorTFT\FullColorDisplayInterface;
use RealityInterface\Displays\Display;
use RealityInterface\Displays\EmbeddedDisplay;
use RealityInterface\Displays\Exceptions\DisplayException;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;

class ColorTFTDisplay extends Display
{
    public function transmit(DumpedBuffer $buffer): void
    {
        /** @var FullColorDisplayInterface $panel */
        $panel = $this->display;

        if (! $panel instanceof FullColorDisplayInterface) {
            throw DisplayException::cannotTransmitTo($panel::class, FullColorDisplayInterface::class);
        }

        $panel->display($buffer);
    }

    public static function as(EmbeddedDisplay $circuit): static
    {
        $attr = reflect_class($circuit, OutputsColor::class);
        if ($attr->getName() == OutputsColor::class) {
            return new static($circuit);
        }

        throw DisplayException::missingRequiredAbility('ColorTFTDisplay', $circuit::class, 'OutputsColor');
    }
}
