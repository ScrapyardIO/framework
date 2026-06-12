<?php

namespace RealityInterface\Displays\Services;

use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;

class BufferTranscoderService
{
    public function __construct(
        protected FormatSpec $display_spec
    ) {}

    public function transcode(DumpedBuffer $dump): DumpedBuffer
    {
        if ($this->formatSpecsAreTheSame($dump->metadata)) {
            return $dump;
        }

        // @todo - convert $dump from its metadata layout into $this->display_spec.
        // Passthrough for now until the per-format encoders are built.
        return $dump;
    }

    protected function formatSpecsAreTheSame(FormatSpec $spec): bool
    {
        return $this->display_spec == $spec;
    }
}
