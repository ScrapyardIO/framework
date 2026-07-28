<?php

namespace DeptOfScrapyardRobotics\Tests\Core;

use Fabricate\Contracts\Core\VisualException;
use Fabricate\Contracts\Displays\Display;
use Fabricate\Contracts\Displays\Interfaces\PanelImplementation;
use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Enums\RenderType;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Rendering\GFXRenderer;
use Fabricate\Core\VisualPresentation;
use Fabricate\Displays\Display as BaseDisplay;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\Framebuffer as BaseFramebuffer;
use Fabricate\Rendering\Renderer2D;
use PHPUnit\Framework\TestCase;

class VisualPresentationTest extends TestCase
{
    public function testItAppliesFormatAwareDefaultTextColor(): void
    {
        $cases = [
            [BitDepth::B1, 1],
            [BitDepth::B12, 0x0FFF],
            [BitDepth::B16, 0xFFFF],
            [BitDepth::B18, 0xFCFCFC],
            [BitDepth::B32, 0xFFFFFFFF],
            [BitDepth::B8, 0xFFFFFFFF],
        ];

        foreach ($cases as [$depth, $expected]) {
            $display = new VisualTestDisplay($depth);
            $renderer = $this->createMock(Renderer2D::class);
            $renderer->expects($this->once())
                ->method('setTextColor')
                ->with($expected)
                ->willReturnSelf();

            new VisualPresentation($display, new VisualTestFramebuffer, $renderer);
        }
    }

    public function testItExposesAndExplicitlyForwardsToEveryComponent(): void
    {
        $display = new VisualTestDisplay;
        $framebuffer = new VisualTestFramebuffer;
        $renderer = $this->createMock(Renderer2D::class);
        $renderer->method('setTextColor')->willReturnSelf();
        $renderer->expects($this->once())
            ->method('drawPixel')
            ->with(2, 3, 4)
            ->willReturnSelf();
        $presentation = new VisualPresentation($display, $framebuffer, $renderer);

        $this->assertSame($display, $presentation->getDisplay());
        $this->assertSame($framebuffer, $presentation->getFramebuffer());
        $this->assertSame($renderer, $presentation->getRenderer());
        $this->assertSame($presentation, $presentation->rendererCall('drawPixel', 2, 3, 4));
        $this->assertSame(8, $presentation->displayCall('width'));
        $this->assertSame(0, $presentation->framebufferCall('getPixel', 0, 0));
    }

    public function testPresentFlushesEveryFrameToTheDisplayAndRemainsFluent(): void
    {
        $display = new VisualTestDisplay;
        $framebuffer = new VisualTestFramebuffer;
        $presentation = new VisualPresentation($display, $framebuffer, $this->createStub(Renderer2D::class));

        $this->assertSame($presentation, $presentation->present());
        $this->assertSame(1, $framebuffer->flush_count);
        $this->assertSame(1, $display->flush_count);
    }

    public function testExplicitForwardingRejectsUnknownMethods(): void
    {
        $presentation = new VisualPresentation(
            new VisualTestDisplay,
            new VisualTestFramebuffer,
            $this->createStub(Renderer2D::class),
        );

        $this->expectException(VisualException::class);
        $presentation->rendererCall('missing');
    }

    public function testPanelBackedDisplayCallsRemainFluent(): void
    {
        $presentation = new VisualPresentation(
            new VisualTestPanelDisplay(new VisualTestPanel),
            new VisualTestFramebuffer,
            $this->createStub(Renderer2D::class),
        );

        $this->assertSame($presentation, $presentation->displayCall('show'));
    }

    public function testItClosesTheDisplayWithoutExposingIt(): void
    {
        $display = new VisualTestDisplay;
        $presentation = new VisualPresentation(
            $display,
            new VisualTestFramebuffer,
            $this->createStub(Renderer2D::class),
        );

        $this->assertSame($presentation, $presentation->close());
        $this->assertSame(1, $display->close_count);
    }

    public function testShouldCloseIsFalseForNonWindowedDisplays(): void
    {
        $presentation = new VisualPresentation(
            new VisualTestDisplay,
            new VisualTestFramebuffer,
            $this->createStub(Renderer2D::class),
        );

        $this->assertFalse($presentation->shouldClose());
    }

    public function testItIsAUnifiedFluentDrawingSurface(): void
    {
        $renderer = $this->createMock(Renderer2D::class);
        $renderer->method('setTextColor')->willReturnSelf();
        $renderer->expects($this->once())->method('fill')->with(0)->willReturnSelf();
        $renderer->expects($this->once())->method('drawRect')->with(1, 2, 3, 4, 5)->willReturnSelf();
        $renderer->expects($this->once())->method('setCursor')->with(6, 7)->willReturnSelf();
        $renderer->expects($this->once())->method('println')->with('ScrapyardIO')->willReturnSelf();
        $renderer->expects($this->once())
            ->method('getTextBounds')
            ->with('ScrapyardIO', 6, 7)
            ->willReturn(['x1' => 6, 'y1' => 7, 'w' => 66, 'h' => 8]);

        $presentation = new VisualPresentation(
            new VisualTestDisplay,
            new VisualTestFramebuffer,
            $renderer,
        );

        $this->assertSame(
            $presentation,
            $presentation
                ->clear()
                ->drawRect(1, 2, 3, 4, 5)
                ->setCursor(6, 7)
                ->println('ScrapyardIO'),
        );
        $this->assertSame(8, $presentation->width());
        $this->assertSame(8, $presentation->height());
        $this->assertSame(
            ['x1' => 6, 'y1' => 7, 'w' => 66, 'h' => 8],
            $presentation->getTextBounds('ScrapyardIO', 6, 7),
        );
    }
}

class VisualTestDisplay implements Display
{
    public int $flush_count = 0;

    public int $close_count = 0;

    public function __construct(
        protected BitDepth $bitDepth = BitDepth::B8,
    ) {}

    public function width(): int
    {
        return 8;
    }

    public function height(): int
    {
        return 8;
    }

    public function formatSpec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::ROW_MAJOR, $this->bitDepth);
    }

    public function flush(DumpedBuffer $frame): void
    {
        $this->flush_count++;
    }

    public function close(): void
    {
        $this->close_count++;
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

class VisualTestPanelDisplay extends BaseDisplay
{
    public function flush(DumpedBuffer $frame): void
    {
    }
}

class VisualTestPanel implements PanelImplementation
{
    public function width(): int
    {
        return 8;
    }

    public function height(): int
    {
        return 8;
    }

    public function formatSpec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8);
    }

    public function transmit(DumpedBuffer $frame): void
    {
    }

    public function close(): void
    {
    }

    public function show(): static
    {
        return $this;
    }
}

class VisualTestFramebuffer extends BaseFramebuffer
{
    public int $flush_count = 0;

    public function __construct()
    {
        parent::__construct(8, 8);
    }

    public function getPixel(int $x, int $y): int
    {
        return 0;
    }

    public function setPixel(int $x, int $y, int $value): static
    {
        return $this;
    }

    public function dump(): array
    {
        return [
            new DumpedBuffer(
                RenderType::FULL,
                new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8),
                array_fill(0, 64, 0),
                width: 8,
                height: 8,
            ),
        ];
    }

    public function flush(): array
    {
        $this->flush_count++;

        return $this->dump();
    }

    public function supportsDisplay(Display $display): bool
    {
        return true;
    }

    public function supportsRenderer(GFXRenderer $renderer): bool
    {
        return true;
    }

    protected function rawDump(): array
    {
        return array_fill(0, 8, array_fill(0, 8, 0));
    }
}
