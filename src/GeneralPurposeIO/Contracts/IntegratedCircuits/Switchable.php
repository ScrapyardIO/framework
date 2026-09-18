<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

/** Panel output on or off; RAM keeps its contents. */
interface Switchable extends DisplayPanel
{
    public function setDisplay(bool $on): void;
}
