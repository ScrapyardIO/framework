<?php

namespace Fabricate\PixelPanels\Panels;

use Fabricate\IntegratedCircuits\Attributes\PixelPanels\MonochromeDisplay;
use Fabricate\IntegratedCircuits\PixelPanel;
use Fabricate\PixelPanels\PanelComponent;
use ReflectionClass;

class MonochromePanel extends PanelComponent
{
    public static function buildWith(PixelPanel $panel): static
    {
        $attributes = (new ReflectionClass($panel))->getAttributes(MonochromeDisplay::class);

        if (count($attributes) === 0) {
            throw new \InvalidArgumentException(
                $panel::class.' must be marked with #[MonochromeDisplay] to build a MonochromePanel.'
            );
        }

        return new static($panel);
    }
}
