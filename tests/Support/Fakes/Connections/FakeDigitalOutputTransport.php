<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\Digital\DigitalOutputTransport;

class FakeDigitalOutputTransport extends DigitalOutputTransport
{
    public bool $state = false;

    /** @var list<bool> */
    public array $writes = [];

    public function __construct(int $pin, public readonly FakeHandle $handle)
    {
        parent::__construct($pin);
    }

    public function read(): bool
    {
        return $this->state;
    }

    public function write(bool $state): bool
    {
        $this->writes[] = $state;

        return $this->state = $state;
    }

    public function close(): void
    {
        $this->handle->closed = true;
    }
}
