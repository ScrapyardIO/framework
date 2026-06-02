<?php

namespace RealityInterface\Sensors\Applied\Accelerometry;

use BareMetal\IntegratedCircuit;
use RealityInterface\Sensors\Attributes\MeasuresAcceleration;
use RealityInterface\Sensors\Contracts\Applied\Accelerometry\GenericAccelerometer;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\Sensor;

class Accelerometer extends Sensor
{
    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function xyz(): array
    {
        /** @var GenericAccelerometer $circuit */
        $circuit = &$this->circuit;

        return $circuit->readXYZ();
    }

    private function magnitude(float $x, float $y, float $z): float
    {
        return sqrt($x ** 2 + $y ** 2 + $z ** 2);
    }

    private function collectSamples(int $samples, int $delay_us): array
    {
        $window = [];
        for ($i = 0; $i < $samples; $i++) {
            $window[] = $this->xyz();
            if ($delay_us > 0 && $i < $samples - 1) {
                usleep($delay_us);
            }
        }

        return $window;
    }

    // -------------------------------------------------------------------------
    // XYZ / raw acceleration
    // -------------------------------------------------------------------------

    public function measure(): AccelerometerReading
    {
        $s = $this->xyz();

        return new AccelerometerReading($s['x'], $s['y'], $s['z'], strtotime('now'));
    }

    public function getXYZ(): array
    {
        return $this->xyz();
    }

    // -------------------------------------------------------------------------
    // Tilt (roll + pitch)
    // -------------------------------------------------------------------------

    public function measureTilt(): TiltReading
    {
        $s = $this->xyz();
        [$roll, $pitch] = $this->computeTilt($s['x'], $s['y'], $s['z']);

        return new TiltReading($roll, $pitch, $s['x'], $s['y'], $s['z'], strtotime('now'));
    }

    /**
     * @return array{roll: float, pitch: float} Angles in degrees.
     */
    public function getTilt(): array
    {
        $s = $this->xyz();
        [$roll, $pitch] = $this->computeTilt($s['x'], $s['y'], $s['z']);

        return ['roll' => $roll, 'pitch' => $pitch];
    }

    private function computeTilt(float $x, float $y, float $z): array
    {
        $roll = round(rad2deg(atan2($y, $z)), 2);
        $pitch = round(rad2deg(atan2(-$x, sqrt($y ** 2 + $z ** 2))), 2);

        return [$roll, $pitch];
    }

    // -------------------------------------------------------------------------
    // Magnitude (total g-force)
    // -------------------------------------------------------------------------

    public function measureMagnitude(): MagnitudeReading
    {
        $s = $this->xyz();
        $mag = round($this->magnitude($s['x'], $s['y'], $s['z']), 4);

        return new MagnitudeReading($mag, $s['x'], $s['y'], $s['z'], strtotime('now'));
    }

    public function getMagnitude(): float
    {
        $s = $this->xyz();

        return round($this->magnitude($s['x'], $s['y'], $s['z']), 4);
    }

    // -------------------------------------------------------------------------
    // Freefall
    // -------------------------------------------------------------------------

    public function measureFreefall(float $threshold = 0.15): FreefallReading
    {
        $s = $this->xyz();
        $mag = round($this->magnitude($s['x'], $s['y'], $s['z']), 4);

        return new FreefallReading($mag < $threshold, $mag, $threshold, $s['x'], $s['y'], $s['z'], strtotime('now'));
    }

    public function isFreefall(float $threshold = 0.15): bool
    {
        return $this->getMagnitude() < $threshold;
    }

    // -------------------------------------------------------------------------
    // Activity (is the sensor moving?)
    // -------------------------------------------------------------------------

    public function measureActivity(int $samples = 5, int $delay_us = 10000, float $threshold = 0.05): ActivityReading
    {
        $window = $this->collectSamples($samples, $delay_us);
        $maxDelta = $this->computeMaxDelta($window);

        return new ActivityReading($maxDelta > $threshold, round($maxDelta, 4), $threshold, $samples, strtotime('now'));
    }

    public function isMoving(int $samples = 5, int $delay_us = 10000, float $threshold = 0.05): bool
    {
        $window = $this->collectSamples($samples, $delay_us);

        return $this->computeMaxDelta($window) > $threshold;
    }

    private function computeMaxDelta(array $window): float
    {
        $max = 0.0;
        for ($i = 1; $i < count($window); $i++) {
            $dx = $window[$i]['x'] - $window[$i - 1]['x'];
            $dy = $window[$i]['y'] - $window[$i - 1]['y'];
            $dz = $window[$i]['z'] - $window[$i - 1]['z'];
            $delta = sqrt($dx ** 2 + $dy ** 2 + $dz ** 2);
            if ($delta > $max) {
                $max = $delta;
            }
        }

        return $max;
    }

    // -------------------------------------------------------------------------
    // Vibration (AC-coupled RMS)
    // -------------------------------------------------------------------------

    public function measureVibration(int $samples = 20, int $delay_us = 5000): VibrationReading
    {
        $rms = $this->computeVibrationRms($samples, $delay_us);

        return new VibrationReading(round($rms, 4), $samples, strtotime('now'));
    }

    public function getVibrationRms(int $samples = 20, int $delay_us = 5000): float
    {
        return round($this->computeVibrationRms($samples, $delay_us), 4);
    }

    private function computeVibrationRms(int $samples, int $delay_us): float
    {
        $window = $this->collectSamples($samples, $delay_us);

        $mx = array_sum(array_column($window, 'x')) / $samples;
        $my = array_sum(array_column($window, 'y')) / $samples;
        $mz = array_sum(array_column($window, 'z')) / $samples;

        $sumSq = 0.0;
        foreach ($window as $s) {
            $sumSq += ($s['x'] - $mx) ** 2 + ($s['y'] - $my) ** 2 + ($s['z'] - $mz) ** 2;
        }

        return sqrt($sumSq / $samples);
    }

    // -------------------------------------------------------------------------
    // Impact (peak g over a sample window)
    // -------------------------------------------------------------------------

    public function measureImpact(int $samples = 10, int $delay_us = 5000): ImpactReading
    {
        $window = $this->collectSamples($samples, $delay_us);
        ['peak' => $peak, 'sample' => $at] = $this->computePeak($window);

        return new ImpactReading(round($peak, 4), $at['x'], $at['y'], $at['z'], $samples, strtotime('now'));
    }

    public function getPeakG(int $samples = 10, int $delay_us = 5000): float
    {
        $window = $this->collectSamples($samples, $delay_us);

        return round($this->computePeak($window)['peak'], 4);
    }

    private function computePeak(array $window): array
    {
        $peak = -1.0;
        $at = $window[0];
        foreach ($window as $s) {
            $mag = $this->magnitude($s['x'], $s['y'], $s['z']);
            if ($mag > $peak) {
                $peak = $mag;
                $at = $s;
            }
        }

        return ['peak' => $peak, 'sample' => $at];
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    public static function as(IntegratedCircuit $circuit): static
    {
        $attr = reflect_class($circuit, MeasuresAcceleration::class);
        if ($attr->getName() == MeasuresAcceleration::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('Accelerometer', $circuit::class, 'MeasuresAcceleration');
    }
}
