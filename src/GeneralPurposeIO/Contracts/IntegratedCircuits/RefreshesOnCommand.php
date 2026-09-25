<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

/** RAM write and visible update are separate steps (ePaper). refresh() shows what transmit() wrote. */
interface RefreshesOnCommand extends DisplayPanel
{
    public function refresh(RefreshMode $mode = RefreshMode::FULL): void;
}
