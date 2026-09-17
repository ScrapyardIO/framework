<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

use Surface\Contracts\Framebuffers\FormatSpec;

interface DisplayPanel extends IntegratedCircuit
{
    public function width(): int;
    public function height(): int;
    
}