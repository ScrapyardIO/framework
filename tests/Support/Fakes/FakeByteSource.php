<?php

namespace ScrapyardIO\Tests\Support\Fakes;

use GeneralPurposeIO\Contracts\Core\ByteSource;
use Throwable;

final class FakeByteSource implements ByteSource
{
    public string $buffered = '';

    public ?Throwable $fault = null;

    public function __construct(public readonly string $path) {}

    public function path(): string { return $this->path; }

    public function pollBytes(int $max_bytes = 4096): string
    {
        if (! is_null($this->fault)) {
            throw $this->fault;
        }

        $out = substr($this->buffered, 0, $max_bytes);
        $this->buffered = substr($this->buffered, strlen($out));

        return $out;
    }
}
