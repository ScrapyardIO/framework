<?php

namespace GeneralPurposeIO\Digital;

class MultipleDigitalPins
{
    /**
     * @param  array<string, DigitalPin>  $pins
     */
    public function __construct(
        public readonly array $pins,
    ) {}

    public function getPin(string $name): ?DigitalPin
    {
        return $this->pins[$name] ?? null;
    }

    public function close(): void
    {
        foreach ($this->pins as $pin) {
            if ($pin instanceof DigitalPin) {
                $pin->close();
            }

            break;
        }
    }
}
