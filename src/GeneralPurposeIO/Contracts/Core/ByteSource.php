<?php

namespace GeneralPurposeIO\Contracts\Core;

/** Something the gpio resource can poll for bytes without waiting. */
interface ByteSource
{
    public function path(): string;

    /** Bytes already buffered, or '' — never blocks. */
    public function pollBytes(int $max_bytes = 4096): string;
}
