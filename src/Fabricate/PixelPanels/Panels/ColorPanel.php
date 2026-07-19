<?php

namespace Fabricate\PixelPanels\Panels;

use Fabricate\IntegratedCircuits\Attributes\PixelPanels\FullColorDisplay;
use Fabricate\IntegratedCircuits\PixelPanel;
use Fabricate\PixelPanels\PanelComponent;
use ReflectionClass;

class ColorPanel extends PanelComponent
{
    public static function buildWith(PixelPanel $panel): static
    {
        $attributes = (new ReflectionClass($panel))->getAttributes(FullColorDisplay::class);

        if (count($attributes) === 0) {
            throw new \InvalidArgumentException(
                $panel::class.' must be marked with #[FullColorDisplay] to build a ColorPanel.'
            );
        }

        return new static($panel);
    }
}
