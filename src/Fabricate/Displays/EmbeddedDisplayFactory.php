<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Displays\DisplayComponent as DisplayComponentInterface;
use Fabricate\Contracts\Displays\DisplayFactory as FactoryContract;

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

    }
}
