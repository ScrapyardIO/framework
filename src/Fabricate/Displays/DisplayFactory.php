<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Displays\DisplayFactory as FactoryContract;
use Fabricate\Contracts\Displays\DisplayComponent as DisplayComponentInterface;

class DisplayFactory implements FactoryContract
{
    private ?DisplayFactory $factory = null;

    public function create(): DisplayComponentInterface
    {
        return $this->factory->create();
    }

    public function renderedUsing(string $driver): static
    {
        $this->factory = $this->factory->renderedUsing($driver);
        return $this;
    }

    public function withFramebuffer(string $driver): static
    {
        $this->factory = $this->factory->withFramebuffer($driver);
        return $this;
    }

    public function embedded(string $driver): static
    {
        $this->factory = new EmbeddedDisplayFactory($driver);
        return $this;
    }

    public function windowed(string $driver): static
    {
        $this->factory = new WindowedDisplayFactory($driver);
        return $this;
    }


}
