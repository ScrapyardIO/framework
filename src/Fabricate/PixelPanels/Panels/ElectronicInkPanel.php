<?php

namespace Fabricate\PixelPanels\Panels;

use Fabricate\IntegratedCircuits\Attributes\PixelPanels\eInk;
use Fabricate\IntegratedCircuits\Attributes\PixelPanels\ePaper;
use Fabricate\IntegratedCircuits\PixelPanel;
use Fabricate\PixelPanels\PanelComponent;
use ReflectionClass;

class ElectronicInkPanel extends PanelComponent
{
    public static function buildWith(PixelPanel $panel): static
    {
        $reflection = new ReflectionClass($panel);
        $has_epaper = count($reflection->getAttributes(ePaper::class)) > 0;
        $has_eink = count($reflection->getAttributes(eInk::class)) > 0;

        if (! $has_epaper && ! $has_eink) {
            throw new \InvalidArgumentException(
                $panel::class.' must be marked with #[ePaper] or #[eInk] to build an ElectronicInkPanel.'
            );
        }

        return new static($panel);
    }
}
