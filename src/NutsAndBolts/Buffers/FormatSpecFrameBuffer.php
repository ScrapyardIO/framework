<?php

namespace ScrapyardIO\NutsAndBolts\Buffers;

use Exception;
use RuntimeException;
use ScrapyardIO\NutsAndBolts\Buffers\Factory\FSBFFactory;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\Endianness;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\ScanDirection;

abstract class FormatSpecFrameBuffer extends Framebuffer
{
    protected static string $factory_class;

    public function __construct(
        int $width,
        int $height,
        protected FormatSpec $format_spec,
    ) {
        parent::__construct($width, $height);
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    abstract public function dump(): array;

    /**
     * Shape the raw logical grid into the layout this buffer's FormatSpec
     * advertises, so every DumpedBuffer carries data already in its declared
     * format and downstream can trust the metadata without re-inspecting it.
     *
     * @return array<int, int>|array<int, array<int, int>>
     */
    protected function formatRawDump(): array
    {
        $raw = $this->rawDump();

        return match ($this->format_spec->pixel_format) {
            PixelFormat::ROW_MAJOR => $this->packRowMajor($raw),
            PixelFormat::MONO_VERTICAL_PAGE => $this->packVerticalPages($raw),
            default => throw new RuntimeException(
                "No packer for pixel format {$this->format_spec->pixel_format->value}."
            ),
        };
    }

    /**
     * Flatten a row-major logical grid into a byte stream, slicing each pixel
     * word into bit_depth-many bytes.
     *
     * The grid already holds panel-native colour words (e.g. RGB565), so this
     * is pure byte-slicing: bit_depth fixes the byte width (B16 = 2, B18 = 3)
     * and endianness picks which end leads (MSB_FIRST is the ST77xx/TFT
     * convention). Missing cells default to 0, so the output is always exactly
     * width · height · bytes_per_pixel long.
     *
     * @param  array<int, array<int, int>>  $grid
     * @return array<int, int>
     */
    protected function packRowMajor(array $grid): array
    {
        $bytes_per_pixel = intdiv($this->format_spec->bit_depth->value + 7, 8);
        $msb_first = ($this->format_spec->endianness !== Endianness::LSB);

        $bytes = [];

        for ($row = 0; $row < $this->height; $row++) {
            for ($col = 0; $col < $this->width; $col++) {
                $pixel = $grid[$row][$col] ?? 0;

                for ($i = 0; $i < $bytes_per_pixel; $i++) {
                    $shift = $msb_first ? (($bytes_per_pixel - 1 - $i) * 8) : ($i * 8);
                    $bytes[] = ($pixel >> $shift) & 0xFF;
                }
            }
        }

        return $bytes;
    }

    /**
     * Pack a row-major logical grid into vertical-page bytes: 8 stacked rows per
     * byte, emitted page-major. BitOrder picks which end of the byte the top row
     * lands on (LSB_FIRST = bit 0, the SSD1306/SH1106 convention); BOTTOM_TO_TOP
     * flips the surface vertically before packing.
     *
     * @param  array<int, array<int, int>>  $grid
     * @return array<int, int>
     */
    protected function packVerticalPages(array $grid): array
    {
        $height = count($grid);
        $width = ($height > 0) ? count($grid[0]) : 0;
        $pages = intdiv($height + 7, 8);

        $msb_first = ($this->format_spec->bit_order === BitOrder::MSB_FIRST);
        $flip_rows = ($this->format_spec->scan_direction === ScanDirection::BOTTOM_TO_TOP);

        $bytes = [];

        for ($page = 0; $page < $pages; $page++) {
            for ($col = 0; $col < $width; $col++) {
                $byte = 0;

                for ($offset = 0; $offset < 8; $offset++) {
                    $row = ($page * 8) + $offset;

                    if ($row >= $height) {
                        continue;
                    }

                    $source_row = $flip_rows ? ($height - 1 - $row) : $row;

                    if (! empty($grid[$source_row][$col])) {
                        $bit = $msb_first ? (7 - $offset) : $offset;
                        $byte |= (1 << $bit);
                    }
                }

                $bytes[] = $byte;
            }
        }

        return $bytes;
    }

    /**
     * @return array<int, DumpedBuffer>
     */
    public function flush(): array
    {
        $data = $this->dump();

        $this->grid->clear();

        return $data;
    }

    /**
     * @throws Exception
     */
    public static function size(int $width, int $height): FSBFFactory
    {
        if (! isset(static::$factory_class)) {
            throw new Exception('Factory class must be set');
        }

        $factory_class = static::$factory_class;

        return new $factory_class($width, $height);
    }
}
