<?php

namespace ScrapyardIO\NutsAndBolts\DataObjects;

use ScrapyardIO\NutsAndBolts\Enums\RenderType;

/**
 * One unit of pixel data a buffer hands downstream.
 *
 * Alongside the raw payload and the {@see FormatSpec} that describes it, a dump
 * carries where the data belongs on the surface (its top-left origin), how big
 * that region is, and whether it is a whole frame or a partial update. Emitting
 * these as a list lets n-frame and partial-refresh buffers send several updates
 * to the transcoder in one pass.
 *
 * width/height describe the region the payload covers. They are nullable so a
 * whole-surface dump can leave them unset and let the panel fall back to its
 * own dimensions; partial updates (e.g. a single page strip) set them so the
 * panel knows the exact window to address.
 */
readonly class DumpedBuffer
{
    /**
     * @param  array<int, int>|array<int, array<int, int>>  $raw_data  Payload already shaped per {@see $metadata} (e.g. packed page bytes or row-major pixels).
     */
    public function __construct(
        public RenderType $render_type,
        public FormatSpec $metadata,
        public array $raw_data,
        public int $origin_x = 0,
        public int $origin_y = 0,
        public ?int $width = null,
        public ?int $height = null,
    ) {}
}
