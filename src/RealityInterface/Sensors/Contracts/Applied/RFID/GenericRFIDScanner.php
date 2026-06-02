<?php

namespace RealityInterface\Sensors\Contracts\Applied\RFID;

interface GenericRFIDScanner
{
    public function scanTargetDetails(int $timeout = 10000): ?array;
}
