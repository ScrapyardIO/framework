<?php

namespace Fabricate\NutsAndBolts\Concerns;

/**
 * Nibble (4-bit) splice helpers — the 4-bit counterpart to {@see Splices16Bits}.
 */
trait Splices4Bits
{
    /**
     * @return array{high: int, low: int}
     */
    public function splitNibbles(int $hex): array
    {
        return [
            'high' => $this->getLowNibble($this->shiftHighToLowNibble($hex)),
            'low' => $this->getLowNibble($hex),
        ];
    }

    public function getLowNibble(int $hex): int
    {
        return $hex & 0x0F;
    }

    public function shiftHighToLowNibble(int $hex): int
    {
        return $hex >> 4;
    }

    public function packNibbles(int $high, int $low): int
    {
        return ($this->getLowNibble($high) << 4) | $this->getLowNibble($low);
    }

    /**
     * Pack a 12-bit 0x0RGB word from three nibbles.
     */
    public function packRgb444(int $r, int $g, int $b): int
    {
        return ($this->getLowNibble($r) << 8)
            | ($this->getLowNibble($g) << 4)
            | $this->getLowNibble($b);
    }

    /**
     * @return array{r: int, g: int, b: int}
     */
    public function splitRgb444(int $hex): array
    {
        return [
            'r' => $this->getLowNibble($this->shiftHighToLowNibble($this->shiftHighToLowNibble($hex))),
            'g' => $this->getLowNibble($this->shiftHighToLowNibble($hex)),
            'b' => $this->getLowNibble($hex),
        ];
    }
}
