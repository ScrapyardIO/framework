<?php

namespace GeneralPurposeIO\Contracts\Core;

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;

/** Something the gpio resource can poll for edges without waiting. */
interface EdgeSource
{
    /** The connection the pin rides on (chip number, MPSSE device name), or null when unbound. */
    public function device(): string|int|null;

    public function offset(): int;

    /** @return list<DigitalEdgeEvent> zero or more edges since the last poll; never blocks */
    public function pollEdges(bool $rising, bool $falling): array;
}
