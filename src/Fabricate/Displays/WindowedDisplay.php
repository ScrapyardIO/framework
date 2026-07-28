<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Displays\Interfaces\SoftwarePanel as PanelInterface;
use Fabricate\Contracts\Displays\WindowedDisplay as DisplayContract;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Rendering\GFXRenderer;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;

class WindowedDisplay extends Display implements DisplayContract
{
    protected bool $close_requested = false;

    public function __construct(
        PanelInterface $panel
    ) {
        parent::__construct($panel);
    }

    public function flush(DumpedBuffer $frame): void
    {
        if ($this->shouldClose()) {
            return;
        }

        $this->panel->transmit($frame);
        $this->refreshCloseState();
    }

    /**
     * Window chrome close / OS quit — sticky once observed.
     */
    public function shouldClose(): bool
    {
        if ($this->close_requested) {
            return true;
        }

        $this->refreshCloseState();

        return $this->close_requested;
    }

    public function supportsRenderer(GFXRenderer $renderer): bool
    {
        return $this->panel->supportsRenderer($renderer);
    }

    public function supportsFramebuffer(Framebuffer $framebuffer): bool
    {
        return $this->panel->supportsFramebuffer($framebuffer);
    }

    protected function refreshCloseState(): void
    {
        if ($this->close_requested) {
            return;
        }

        $panel = $this->panel;

        if (method_exists($panel, 'shouldClose') && $panel->shouldClose()) {
            $this->close_requested = true;
        }
    }
}