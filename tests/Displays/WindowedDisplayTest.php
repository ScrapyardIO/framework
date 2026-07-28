<?php

namespace DeptOfScrapyardRobotics\Tests\Displays;

use Fabricate\Contracts\Displays\Interfaces\SoftwarePanel;
use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Enums\RenderType;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Rendering\GFXRenderer;
use Fabricate\Displays\WindowedDisplay;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\FormatSpec;
use PHPUnit\Framework\TestCase;

class WindowedDisplayTest extends TestCase
{
    public function testShouldCloseIsStickyOnceThePanelReportsClose(): void
    {
        $panel = new WindowCloseTestPanel;
        $display = new WindowedDisplay($panel);

        $this->assertFalse($display->shouldClose());

        $panel->close_requested = true;

        $this->assertTrue($display->shouldClose());
        $panel->close_requested = false;
        $this->assertTrue($display->shouldClose());
    }

    public function testFlushSkipsTransmitAfterCloseIsRequested(): void
    {
        $panel = new WindowCloseTestPanel;
        $display = new WindowedDisplay($panel);
        $panel->close_requested = true;

        $display->flush(new DumpedBuffer(
            RenderType::FULL,
            new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B32),
            [],
            width: 1,
            height: 1,
        ));

        $this->assertSame(0, $panel->transmit_count);
    }
}

class WindowCloseTestPanel implements SoftwarePanel
{
    public bool $close_requested = false;

    public int $transmit_count = 0;

    public function width(): int
    {
        return 1;
    }

    public function height(): int
    {
        return 1;
    }

    public function formatSpec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B32);
    }

    public function transmit(DumpedBuffer $frame): void
    {
        $this->transmit_count++;
    }

    public function close(): void
    {
    }

    public function shouldClose(): bool
    {
        return $this->close_requested;
    }

    public function supportsRenderer(GFXRenderer $renderer): bool
    {
        return true;
    }

    public function supportsFramebuffer(Framebuffer $framebuffer): bool
    {
        return true;
    }
}
