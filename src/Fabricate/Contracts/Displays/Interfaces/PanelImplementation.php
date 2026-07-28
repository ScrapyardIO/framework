<?php

namespace Fabricate\Contracts\Displays\Interfaces;

use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\FormatSpec;

interface PanelImplementation
{
    public function width(): int;
    public function height(): int;
    public function formatSpec(): FormatSpec;
    public function transmit(DumpedBuffer $frame): void;
    public function close(): void;
}