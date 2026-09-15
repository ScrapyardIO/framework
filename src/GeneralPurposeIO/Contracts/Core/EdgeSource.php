<?php

namespace GeneralPurposeIO\Contracts\Core;

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;

/** Something the gpio resource can poll for edges without waiting. */
interface EdgeSource
{
    public function offset(): int;

    /** @return list<DigitalEdgeEvent> zero or more edges since the last poll; never blocks */
    public function pollEdges(bool $rising = true, bool $falling = false): array;
}
