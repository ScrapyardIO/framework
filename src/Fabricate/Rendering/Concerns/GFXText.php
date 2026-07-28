<?php

namespace Fabricate\Rendering\Concerns;

use Fabricate\Rendering\Fonts\ClassicFont;
use Fabricate\Rendering\Fonts\GFXFont;

trait GFXText
{
    protected int $cursor_x = 0;

    protected int $cursor_y = 0;

    protected bool $wrap = true;

    protected bool $cp437 = false;

    protected int $text_size_x = 1;

    protected int $text_size_y = 1;

    protected ?GFXFont $font = null;

    protected ?ClassicFont $classic_font = null;

    protected ?int $text_color = null;

    protected ?int $text_bg_color = null;

    public function write(int $c): static
    {
        if (is_null($this->font)) {
            // Classic built-in font
            if ($c === ord("\n")) {
                $this->cursor_x = 0;
                $this->cursor_y += $this->text_size_y * 8;
            } elseif ($c !== ord("\r")) {
                if ($this->wrap && (($this->cursor_x + $this->text_size_x * 6) > $this->width())) {
                    $this->cursor_x = 0;
                    $this->cursor_y += $this->text_size_y * 8;
                }
                $this->drawChar($this->cursor_x, $this->cursor_y, $c, $this->text_color, $this->text_bg_color, $this->text_size_x, $this->text_size_y);
                $this->cursor_x += $this->text_size_x * 6;
            }
        } else {
            // Custom font
            if ($c === ord("\n")) {
                $this->cursor_x = 0;
                $this->cursor_y += $this->text_size_y * $this->font->getYAdvance();
            } elseif ($c !== ord("\r")) {
                $first = $this->font->getFirst();
                $last = $this->font->getLast();

                if (($c >= $first) && ($c <= $last)) {
                    $glyphInfo = $this->font->getGlyphInfo($c);
                    $w = $glyphInfo['width'];
                    $h = $glyphInfo['height'];

                    if (($w > 0) && ($h > 0)) {
                        $xo = $glyphInfo['xOffset'];

                        if ($this->wrap && (($this->cursor_x + $this->text_size_x * ($xo + $w)) > $this->width())) {
                            $this->cursor_x = 0;
                            $this->cursor_y += $this->text_size_y * $this->font->getYAdvance();
                        }
                        $this->drawChar($this->cursor_x, $this->cursor_y, $c, $this->text_color, $this->text_bg_color, $this->text_size_x, $this->text_size_y);
                    }
                    $this->cursor_x += $glyphInfo['xAdvance'] * $this->text_size_x;
                }
            }
        }

        return $this;
    }

    public function drawChar(int $x, int $y, int $c, int $color, int $bg, int $size_x, int $size_y): static
    {
        if (is_null($this->font)) {
            // Classic built-in font (5x7 in column-major format)
            if (is_null($this->classic_font)) {
                $this->classic_font = new ClassicFont;
            }

            // Clip check
            if (($x >= $this->width()) ||
                ($y >= $this->height()) ||
                (($x + 6 * $size_x - 1) < 0) ||
                (($y + 8 * $size_y - 1) < 0)) {
                return $this;
            }

            // Handle 'classic' charset behavior
            if (! $this->cp437 && ($c >= 176)) {
                $c++;
            }

            // Classic font optimization: Draw background as single rect if needed
            if ($bg != $color) {
                $this->fillRect($x, $y, 6 * $size_x, 8 * $size_y, $bg);
            }

            // Then draw foreground pixels column-by-column
            for ($i = 0; $i < 5; $i++) {
                $line = $this->classic_font->getCharByte($c, $i);

                for ($j = 0; $j < 8; $j++) {
                    if ($line & 1) {
                        if ($size_x == 1 && $size_y == 1) {
                            $this->drawPixel($x + $i, $y + $j, $color);
                        } else {
                            $this->fillRect($x + $i * $size_x, $y + $j * $size_y, $size_x, $size_y, $color);
                        }
                    }
                    $line >>= 1;
                }
            }
        } else {
            // Custom font
            $glyphInfo = $this->font->getGlyphInfo($c);

            if ($glyphInfo['valid'] === 0) {
                return $this;
            }

            $bo = $glyphInfo['bitmapOffset'];
            $w = $glyphInfo['width'];
            $h = $glyphInfo['height'];
            $xo = $glyphInfo['xOffset'];
            $yo = $glyphInfo['yOffset'];
            if ($this->font->getYOffsetMode() === 'lvgl_line') {
                $yo = $this->font->getYAdvance() - $h - $yo;
            } elseif ($this->font->getFontEncoding() === 'lvgl') {
                // Montserrat-like converted LVGL fonts can carry raw yOffsets where
                // shorter lowercase glyphs appear superscripted. Align by cap height.
                $capHeight = $this->font->getCapHeight();
                if ($yo >= 0 && $h < $capHeight) {
                    $yo += ($capHeight - $h);
                }
            }

            $xo16 = 0;
            $yo16 = 0;

            if ($size_x > 1 || $size_y > 1) {
                $xo16 = $xo;
                $yo16 = $yo;
            }

            // NOTE: Custom fonts don't support background color by design
            // See Adafruit's comments about this

            $bpp = $this->font->getBitsPerPixel();

            if ($bpp === 1) {
                // Standard 1bpp font rendering (Adafruit GFX format)
                $bits = 0;
                $bit = 0;

                for ($yy = 0; $yy < $h; $yy++) {
                    for ($xx = 0; $xx < $w; $xx++) {
                        if (! ($bit++ & 7)) {
                            $bits = $this->font->getBitmapByte($bo++);
                        }

                        if ($bits & 0x80) {
                            if ($size_x == 1 && $size_y == 1) {
                                $this->drawPixel($x + $xo + $xx, $y + $yo + $yy, $color);
                            } else {
                                $this->fillRect(
                                    $x + ($xo16 + $xx) * $size_x,
                                    $y + ($yo16 + $yy) * $size_y,
                                    $size_x,
                                    $size_y,
                                    $color
                                );
                            }
                        }
                        $bits <<= 1;
                    }
                }
            } elseif ($bpp === 4) {
                // 4bpp anti-aliased font rendering (LVGL format)
                // Each nibble is a pixel intensity 0x0 (transparent) to 0xF (opaque)
                // Nibbles are packed continuously, no row padding, high nibble first
                $alphaThreshold = $this->font->getAlphaThreshold();
                $nibbleIndex = 0;
                $currentByte = 0;

                for ($yy = 0; $yy < $h; $yy++) {
                    for ($xx = 0; $xx < $w; $xx++) {
                        if (($nibbleIndex & 1) === 0) {
                            // Even nibble index: read new byte, use high nibble
                            $currentByte = $this->font->getBitmapByte($bo++);
                            $alpha = ($currentByte >> 4) & 0x0F;
                        } else {
                            // Odd nibble index: use low nibble of current byte
                            $alpha = $currentByte & 0x0F;
                        }
                        $nibbleIndex++;

                        // Threshold is configurable per font.
                        if ($alpha >= $alphaThreshold) {
                            if ($size_x == 1 && $size_y == 1) {
                                $this->drawPixel($x + $xo + $xx, $y + $yo + $yy, $color);
                            } else {
                                $this->fillRect(
                                    $x + ($xo16 + $xx) * $size_x,
                                    $y + ($yo16 + $yy) * $size_y,
                                    $size_x,
                                    $size_y,
                                    $color
                                );
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    public function print(string $str): static
    {
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $this->write(ord($str[$i]));
        }

        return $this;
    }

    public function println(string $str = ''): static
    {
        $this->print($str);
        $this->write(ord("\n"));

        return $this;
    }

    public function setTextSize(int $s, ?int $y = null): static
    {
        $this->text_size_x = ($s > 0) ? $s : 1;
        $this->text_size_y = (! is_null($y) && $y > 0) ? $y : $this->text_size_x;

        return $this;
    }

    public function setCursor(int $x, int $y): static
    {
        $this->cursor_x = $x;
        $this->cursor_y = $y;

        return $this;
    }

    public function getCursorX(): int
    {
        return $this->cursor_x;
    }

    public function getCursorY(): int
    {
        return $this->cursor_y;
    }

    public function setTextColor(int $color, ?int $bg = null): static
    {
        $this->text_color = $color;
        $this->text_bg_color = $bg ?? $color;

        return $this;
    }

    public function setTextWrap(bool $wrap): static
    {
        $this->wrap = $wrap;

        return $this;
    }

    public function setFont(object|string|null $f = null): static
    {
        $resolved = $this->resolveFontArgument($f);

        if (! is_null($resolved)) {
            if (is_null($this->font)) {
                // Switching from classic to custom font.
                // Adafruit custom fonts use baseline-based y offsets; LVGL fonts in this
                // project use line-based offsets and don't need this legacy shift.
                if ($resolved->getFontEncoding() !== 'lvgl') {
                    $this->cursor_y += 6;
                }
            }
        } elseif (! is_null($this->font)) {
            // Switching from custom to classic font
            // Move cursor up only when leaving Adafruit baseline-based custom fonts.
            if ($this->font->getFontEncoding() !== 'lvgl') {
                $this->cursor_y -= 6;
            }
        }

        $this->font = $resolved;

        return $this;
    }

    /**
     * Resolve a setFont argument to a custom GFXFont instance, or null for classic.
     *
     * The classic font stays on the optimized null path — registering ClassicFont
     * via the FontRegistry is for discovery/about, not for this drawing path.
     */
    protected function resolveFontArgument(object|string|null $f): ?GFXFont
    {
        if (is_null($f)) {
            return null;
        }

        if (is_string($f)) {
            if ($f === 'classic') {
                return null;
            }

            $resolved = app('font')->font($f);

            return $resolved instanceof ClassicFont ? null : $resolved;
        }

        if (! $f instanceof GFXFont) {
            throw new \InvalidArgumentException(
                'setFont() expects a GFXFont instance, registered font name, or null.'
            );
        }

        return $f instanceof ClassicFont ? null : $f;
    }

    public function setCp437(bool $enable): static
    {
        $this->cp437 = $enable;

        return $this;
    }

    public function charBounds(int $c, int &$x, int &$y, int &$min_x, int &$min_y, int &$max_x, int &$max_y): static
    {
        if (! is_null($this->font)) {
            // Custom font
            if ($c === ord("\n")) {
                $x = 0;
                $y += $this->text_size_y * $this->font->getYAdvance();
            } elseif ($c !== ord("\r")) {
                $first = $this->font->getFirst();
                $last = $this->font->getLast();

                if (($c >= $first) && ($c <= $last)) {
                    $glyphInfo = $this->font->getGlyphInfo($c);
                    $gw = $glyphInfo['width'];
                    $gh = $glyphInfo['height'];
                    $xa = $glyphInfo['xAdvance'];
                    $xo = $glyphInfo['xOffset'];
                    $yo = $glyphInfo['yOffset'];
                    if ($this->font->getYOffsetMode() === 'lvgl_line') {
                        $yo = $this->font->getYAdvance() - $gh - $yo;
                    } elseif ($this->font->getFontEncoding() === 'lvgl') {
                        $capHeight = $this->font->getCapHeight();
                        if ($yo >= 0 && $gh < $capHeight) {
                            $yo += ($capHeight - $gh);
                        }
                    }

                    if ($this->wrap && (($x + (($xo + $gw) * $this->text_size_x)) > $this->width())) {
                        $x = 0;
                        $y += $this->text_size_y * $this->font->getYAdvance();
                    }

                    $tsx = $this->text_size_x;
                    $tsy = $this->text_size_y;
                    $x1 = $x + $xo * $tsx;
                    $y1 = $y + $yo * $tsy;
                    $x2 = $x1 + $gw * $tsx - 1;
                    $y2 = $y1 + $gh * $tsy - 1;

                    if ($x1 < $min_x) {
                        $min_x = $x1;
                    }
                    if ($y1 < $min_y) {
                        $min_y = $y1;
                    }
                    if ($x2 > $max_x) {
                        $max_x = $x2;
                    }
                    if ($y2 > $max_y) {
                        $max_y = $y2;
                    }

                    $x += $xa * $tsx;
                }
            }
        } else {
            // Classic font
            if ($c === ord("\n")) {
                $x = 0;
                $y += $this->text_size_y * 8;
            } elseif ($c !== ord("\r")) {
                if ($this->wrap && (($x + $this->text_size_x * 6) > $this->width())) {
                    $x = 0;
                    $y += $this->text_size_y * 8;
                }

                $x2 = $x + $this->text_size_x * 6 - 1;
                $y2 = $y + $this->text_size_y * 8 - 1;

                if ($x2 > $max_x) {
                    $max_x = $x2;
                }
                if ($y2 > $max_y) {
                    $max_y = $y2;
                }
                if ($x < $min_x) {
                    $min_x = $x;
                }
                if ($y < $min_y) {
                    $min_y = $y;
                }

                $x += $this->text_size_x * 6;
            }
        }

        return $this;
    }

    public function getTextBounds(string $str, int $x, int $y): array
    {
        $min_x = 0x7FFF;
        $min_y = 0x7FFF;
        $max_x = -1;
        $max_y = -1;

        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            $this->charBounds($c, $x, $y, $min_x, $min_y, $max_x, $max_y);
        }

        $x1 = $x;
        $y1 = $y;
        $w = 0;
        $h = 0;

        if ($max_x >= $min_x) {
            $x1 = $min_x;
            $w = $max_x - $min_x + 1;
        }

        if ($max_y >= $min_y) {
            $y1 = $min_y;
            $h = $max_y - $min_y + 1;
        }

        return ['x1' => $x1, 'y1' => $y1, 'w' => $w, 'h' => $h];
    }
}
