<?php

namespace RealityInterface\Sensors\Applied\RFID;

use BareMetal\IntegratedCircuit;
use RealityInterface\Sensors\Attributes\NearFieldCommunications;
use RealityInterface\Sensors\Contracts\Applied\RFID\GenericRFIDScanner;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\Sensor;

class RFIDScanner extends Sensor
{
    public function scanTargetDetails(int $timeout = 10000): ?RFIDTarget
    {
        /** @var GenericRFIDScanner $circuit */
        $circuit = &$this->circuit;

        $results = $circuit->scanTargetDetails($timeout);

        if ($results) {
            $results = new RFIDTarget(...$results);
        }

        return $results;
    }

    public static function as(IntegratedCircuit $circuit): static
    {
        $attr = reflect_class($circuit, NearFieldCommunications::class);
        if ($attr->getName() == NearFieldCommunications::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('RFIDScanner', $circuit::class, 'NearFieldCommunications');
    }
}
