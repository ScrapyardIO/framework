<?php

namespace RealityInterface\Sensors\Contracts\Applied\Accelerometry;

interface GenericAccelerometer
{
    /**
     * Read the current acceleration on all three axes.
     *
     * @return array{x: float, y: float, z: float} Values in g.
     */
    public function readXYZ(): array;
}
