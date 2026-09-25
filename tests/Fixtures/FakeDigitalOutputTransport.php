<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\Digital\DigitalOutputTransport;

final class FakeDigitalOutputTransport extends DigitalOutputTransport
{
    public bool $level = false;

    public bool $released = false;

    public function read(): bool
    {
        $this->ensureOpen();

        return $this->level;
    }

    public function write(bool $state): bool
    {
        $this->ensureOpen();
        $this->level = $state;

        return $this->read();
    }

    protected function release(): void
    {
        $this->released = true;
    }
}
