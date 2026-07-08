<?php

namespace BareMetal\GFX\Fonts;

/**
 * Renderer-agnostic glyph data model.
 *
 * Fonts carry no drawing logic — each renderer decodes glyphs into its own
 * drawPixel calls — so the same font classes serve every GFX driver
 * (software phpdafruit, SDL3, …).
 */
abstract class GFXFont
{
    protected array $bitmaps = [];
    protected array $glyphs = [];
    protected int $first = 32;   // First character (usually space)
    protected int $last = 126;   // Last character (usually tilde)
    protected int $yAdvance = 1; // Line height
    protected bool $isColumnMajor = false;  // Classic font uses column-major, custom fonts use row-major
    protected int $bitsPerPixel = 1; // 1 = standard 1bpp, 4 = anti-aliased 4bpp (LVGL)
    protected string $fontEncoding = 'auto'; // auto | adafruit | lvgl
    protected string $yOffsetMode = 'auto'; // auto | raw | lvgl_line
    protected int $alphaThreshold = 8; // only used for 4bpp fonts
    protected ?string $resolvedFontEncoding = null;
    protected ?string $resolvedYOffsetMode = null;
    protected ?int $capHeight = null;

    public function getFirst(): int
    {
        return $this->first;
    }

    public function getLast(): int
    {
        return $this->last;
    }

    public function getYAdvance(): int
    {
        return $this->yAdvance;
    }

    public function isColumnMajor(): bool
    {
        return $this->isColumnMajor;
    }

    public function getBitsPerPixel(): int
    {
        return $this->bitsPerPixel;
    }

    public function getFontEncoding(): string
    {
        return $this->resolveFontEncoding();
    }

    public function getAlphaThreshold(): int
    {
        return $this->alphaThreshold;
    }

    public function getYOffsetMode(): string
    {
        return $this->resolveYOffsetMode();
    }

    public function getCapHeight(): int
    {
        if (! is_null($this->capHeight)) {
            return $this->capHeight;
        }

        $maxHeight = 0;
        $start = max($this->first, 65); // 'A'
        $end = min($this->last, 90);    // 'Z'

        for ($c = $start; $c <= $end; $c++) {
            $glyph = $this->getGlyphInfo($c);
            if (($glyph['valid'] ?? 0) === 1) {
                $h = (int) ($glyph['height'] ?? 0);
                if ($h > $maxHeight) {
                    $maxHeight = $h;
                }
            }
        }

        // Fallback for symbol-only fonts.
        if ($maxHeight <= 0) {
            $maxHeight = (int) $this->yAdvance;
        }

        $this->capHeight = $maxHeight;

        return $this->capHeight;
    }

    public function getByte(int $offset): int
    {
        return $this->bitmaps[$offset];
    }

    public function getBitmapByte(int $offset): int
    {
        return $this->getByte($offset);
    }

    public function getGlyph(int $char_code): array
    {
        // For fonts with glyphs array, return the metadata
        // Format: [bitmapOffset, width, height, xAdvance, xOffset, yOffset]
        if (isset($this->glyphs[$char_code])) {
            return $this->glyphs[$char_code];
        }

        // Return empty glyph if not found
        return [0, 0, 0, 0, 0, 0];
    }

    /**
     * Get glyph information for a character with validation
     * Returns array with: [bitmapOffset, width, height, xAdvance, xOffset, yOffset, valid]
     *
     * @param int $character The character code to get info for
     * @return array Glyph info array with valid flag (1 if valid, 0 if not)
     */
    public function getGlyphInfo(int $character): array
    {
        // Check if character is within font range
        if ($character < $this->first || $character > $this->last) {
            // Return invalid glyph info
            return [0, 0, 0, 0, 0, 0, 0];
        }

        // Adjust character to array index (subtract first char)
        $adjusted_char = $character - $this->first;

        // LVGL converted fonts in this project include an explicit reserved glyph at index 0.
        if ($this->resolveFontEncoding() === 'lvgl') {
            $adjusted_char += 1;
        }

        // For fonts with glyphs array (custom fonts)
        if (isset($this->glyphs[$adjusted_char])) {
            $glyph = $this->glyphs[$adjusted_char];

            // Return glyph info with valid flag
            // Format: [bitmapOffset, width, height, xAdvance, xOffset, yOffset, valid]
            return [
                "bitmapOffset" => $glyph[0],
                "width" => $glyph[1],
                "height" => $glyph[2],
                "xAdvance" => $glyph[3],
                "xOffset" => $glyph[4],
                "yOffset" => $glyph[5],
                "valid" => 1
            ];
        }

        // For classic fixed-width font (no glyphs array)
        // Classic font is 5 pixels wide, 8 pixels tall (with spacing)
        return [
            "bitmapOffset" => $character * 5,  // 5 bytes per char
            "width" => 5,
            "height" => 8,
            "xAdvance" => 6,           // 5 + 1 spacing
            "xOffset" => 0,
            "yOffset" => 0,            // classic draws from top-left, not baseline
            "valid" => 1
        ];
    }

    protected function resolveFontEncoding(): string
    {
        if (! is_null($this->resolvedFontEncoding)) {
            return $this->resolvedFontEncoding;
        }

        if ($this->fontEncoding === 'adafruit' || $this->fontEncoding === 'lvgl') {
            $this->resolvedFontEncoding = $this->fontEncoding;

            return $this->resolvedFontEncoding;
        }

        // Auto-detect: LVGL converted fonts in this project have a reserved
        // all-zero glyph entry at index 0 and then glyphs for the real range.
        $rangeLength = $this->last - $this->first + 1;
        $hasReservedGlyph0 = $this->hasReservedGlyph0();
        $hasExpectedGlyphCount = count($this->glyphs) >= ($rangeLength + 1);

        if ($hasReservedGlyph0 && $hasExpectedGlyphCount) {
            $this->resolvedFontEncoding = 'lvgl';

            return $this->resolvedFontEncoding;
        }

        $this->resolvedFontEncoding = 'adafruit';

        return $this->resolvedFontEncoding;
    }

    protected function hasReservedGlyph0(): bool
    {
        $glyph0 = $this->glyphs[0] ?? null;
        if (! is_array($glyph0) || count($glyph0) < 6) {
            return false;
        }

        return
            $glyph0[0] === 0 &&
            $glyph0[1] === 0 &&
            $glyph0[2] === 0 &&
            $glyph0[3] === 0 &&
            $glyph0[4] === 0 &&
            $glyph0[5] === 0;
    }

    protected function resolveYOffsetMode(): string
    {
        if (! is_null($this->resolvedYOffsetMode)) {
            return $this->resolvedYOffsetMode;
        }

        if ($this->yOffsetMode === 'raw' || $this->yOffsetMode === 'lvgl_line') {
            $this->resolvedYOffsetMode = $this->yOffsetMode;

            return $this->resolvedYOffsetMode;
        }

        // Non-LVGL fonts use Adafruit-style raw yOffset semantics.
        if ($this->resolveFontEncoding() !== 'lvgl') {
            $this->resolvedYOffsetMode = 'raw';

            return $this->resolvedYOffsetMode;
        }

        // Auto detect LVGL y-offset semantics:
        // - Unscii-like converted fonts: all non-negative ofs_y -> line-space coords
        // - Montserrat-like converted fonts: mixed/negative yOffset -> already usable raw
        $start = 1; // LVGL mapped fonts in this project reserve glyph[0]
        $end = min(count($this->glyphs), $start + 96);
        $hasNegative = false;

        for ($i = $start; $i < $end; $i++) {
            $glyph = $this->glyphs[$i] ?? null;
            if (! is_array($glyph) || count($glyph) < 6) {
                continue;
            }

            if ($glyph[5] < 0) {
                $hasNegative = true;
                break;
            }
        }

        $this->resolvedYOffsetMode = $hasNegative ? 'raw' : 'lvgl_line';

        return $this->resolvedYOffsetMode;
    }
}
