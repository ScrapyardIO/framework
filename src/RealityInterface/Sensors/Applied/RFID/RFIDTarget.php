<?php

namespace RealityInterface\Sensors\Applied\RFID;

class RFIDTarget
{
    public function __construct(
        public array $atqa,
        public int $sak,
        public array $uid,
    ) {}
}
