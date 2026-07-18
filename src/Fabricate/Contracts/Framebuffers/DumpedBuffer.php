<?php

namespace Fabricate\Contracts\Framebuffers;

use Fabricate\Contracts\Framebuffers\FormatSpec;
use Fabricate\Contracts\Framebuffers\Enums\RenderType;

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
