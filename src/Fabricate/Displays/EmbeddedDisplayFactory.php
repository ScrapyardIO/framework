<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Displays\DisplayComponent as DisplayComponentInterface;
use Fabricate\Contracts\Displays\DisplayException;
use Fabricate\NutsAndBolts\MagicAliases\Framebuffer;
use Fabricate\NutsAndBolts\MagicAliases\Rendering;
use Fabricate\PixelPanels\PanelComponent;

class EmbeddedDisplayFactory extends DisplayFactory
{
    public ?string $render_driver = null;

    public ?string $framebuffer = null;

    public function __construct(
        public string $embedded_driver
    ) {}

    public function renderedUsing(string $driver): static
    {
        $this->render_driver = $driver;

        return $this;
    }

    public function withFramebuffer(string $driver): static
    {
        $this->framebuffer = $driver;

        return $this;
    }

    public function create(): DisplayComponentInterface
    {
        if (is_null($this->render_driver)) {
            throw new DisplayException('Call renderedUsing() before create().');
        }

        $config = config("integrated-circuits.displays.{$this->embedded_driver}");

        if (! is_array($config)) {
            throw new DisplayException("Embedded display [{$this->embedded_driver}] is not registered in integrated-circuits.displays.");
        }

        if (! isset($config['circuit'], $config['component'], $config['params']['transport'])) {
            throw new DisplayException(
                "Embedded display [{$this->embedded_driver}] config requires circuit, component, and params.transport."
            );
        }

        $circuit_class = $config['circuit'];
        $component_class = $config['component'];
        $transport = $config['params']['transport'];

        if (! isset($transport['protocol'], $transport['args'])) {
            throw new DisplayException(
                "Embedded display [{$this->embedded_driver}] transport requires protocol and args."
            );
        }

        if (! is_subclass_of($component_class, PanelComponent::class)) {
            throw new DisplayException(
                "Embedded display [{$this->embedded_driver}] component must extend PanelComponent."
            );
        }

        $protocol = $transport['protocol'];
        $panel_ic = $circuit_class::{$protocol}(...$transport['args']);

        /** @var PanelComponent $embedded */
        $embedded = $component_class::buildWith($panel_ic);
        $output = new EmbeddedVisualOutput($embedded);

        $gfx = Rendering::engine($this->render_driver)->renderer;

        if (! is_null($this->framebuffer)) {
            $framebuffer = Framebuffer::make(
                $this->framebuffer,
                $output->width(),
                $output->height(),
                $output->formatSpec(),
            );
        } else {
            $framebuffer = $gfx::preferredFramebuffer(
                $output->formatSpec(),
                $output->width(),
                $output->height(),
            );
        }

        return new DisplayComponent($output, $framebuffer, $gfx);
    }
}
