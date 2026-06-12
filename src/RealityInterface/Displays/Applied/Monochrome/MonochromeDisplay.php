<?php

namespace RealityInterface\Displays\Applied\Monochrome;

use RealityInterface\Displays\Attributes\OutputsOnlyBlackAndWhite;
use RealityInterface\Displays\Contracts\Applied\Monochrome\MonochromeDisplayInterface;
use RealityInterface\Displays\Display;
use RealityInterface\Displays\EmbeddedDisplay;
use RealityInterface\Displays\Exceptions\DisplayException;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;

class MonochromeDisplay extends Display
{
    public function transmit(DumpedBuffer $buffer): void
    {
        /** @var MonochromeDisplayInterface $panel */
        $panel = $this->display;

        if (! $panel instanceof MonochromeDisplayInterface) {
            throw DisplayException::cannotTransmitTo($panel::class, MonochromeDisplayInterface::class);
        }

        $panel->display($buffer);
    }

    public static function as(EmbeddedDisplay $circuit): static
    {
        $attr = reflect_class($circuit, OutputsOnlyBlackAndWhite::class);
        if ($attr->getName() == OutputsOnlyBlackAndWhite::class) {
            return new static($circuit);
        }

        throw DisplayException::missingRequiredAbility('MonochromeDisplay', $circuit::class, 'OutputsOnlyBlackAndWhite');
    }
}
