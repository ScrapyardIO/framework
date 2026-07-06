<?php

namespace ScrapyardIO\NutsAndBolts\Concerns;

trait Splices16Bits
{
    public function splitBytes(int $hex): array
    {
        return [
            'high' => $this->getLowByte($this->shiftHighToLowByte($hex)),
            'low' => $this->getLowByte($hex),
        ];
    }

    public function getLowByte(int $hex): int
    {
        return $hex & 0xFF;
    }

    public function shiftHighToLowByte(int $hex): int
    {
        return $hex >> 8;
    }

    protected function s16le(int $lsb, int $msb): int
    {
        $value = (($msb & 0xFF) << 8) | ($lsb & 0xFF);

        return ($value & 0x8000) ? $value - 0x10000 : $value;
    }
}
