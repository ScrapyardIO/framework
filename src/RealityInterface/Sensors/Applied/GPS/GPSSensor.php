<?php

namespace RealityInterface\Sensors\Applied\GPS;

use DateTimeImmutable;
use RealityInterface\Sensors\Attributes\TriangulatesPosition;
use RealityInterface\Sensors\Contracts\Applied\GPS\GlobalPositioningSystem;
use RealityInterface\Sensors\Enums\GpsFixQuality;
use RealityInterface\Sensors\Exceptions\SensorException;
use RealityInterface\Sensors\Sensor;
use RealityInterface\Sensors\SensorChip;

/**
 * Driver-agnostic GPS facade. Wrap any chip that carries the
 * {@see TriangulatesPosition} attribute (and therefore implements
 * {@see GlobalPositioningSystem}) via {@see self::as()} or {@see self::using()}
 * to get one common API regardless of the underlying module.
 */
class GPSSensor extends Sensor
{
    public function update(): bool
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->update();
    }

    public function sendCommand(string $command, bool $add_checksum = true): void
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        $circuit->sendCommand($command, $add_checksum);
    }

    /**
     * @return list<int>
     */
    public function read(int $num_bytes): array
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->read($num_bytes);
    }

    public function readLine(): ?string
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->readLine();
    }

    /**
     * @param  string|list<int>  $data
     */
    public function write(string|array $data): int
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->write($data);
    }

    public function fixQuality(): ?GpsFixQuality
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->fixQuality();
    }

    public function getAltitudeM(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getAltitudeM();
    }

    public function getDatetime(): ?DateTimeImmutable
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getDatetime();
    }

    public function getDebug(): bool
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getDebug();
    }

    public function setDebug(bool $debug): void
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        $circuit->setDebug($debug);
    }

    public function getHas3dFix(): bool
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getHas3dFix();
    }

    public function getHasFix(): bool
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getHasFix();
    }

    public function getHdop(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getHdop();
    }

    public function getHeightGeoid(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getHeightGeoid();
    }

    public function getHorizontalDilution(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getHorizontalDilution();
    }

    public function getInWaiting(): int
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getInWaiting();
    }

    public function getIsActiveData(): ?string
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getIsActiveData();
    }

    public function getLatitude(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getLatitude();
    }

    public function getLatitudeDegrees(): ?int
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getLatitudeDegrees();
    }

    public function getLatitudeMinutes(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getLatitudeMinutes();
    }

    public function getLongitude(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getLongitude();
    }

    public function getLongitudeDegrees(): ?int
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getLongitudeDegrees();
    }

    public function getLongitudeMinutes(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getLongitudeMinutes();
    }

    public function getMessNum(): ?int
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getMessNum();
    }

    public function getNmeaSentence(): ?string
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getNmeaSentence();
    }

    public function getPdop(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getPdop();
    }

    /**
     * @return list<int>
     */
    public function getSatPrns(): array
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getSatPrns();
    }

    public function getSatellites(): ?int
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getSatellites();
    }

    public function getSatellitesPrev(): ?int
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getSatellitesPrev();
    }

    /**
     * @return array<int, object>
     */
    public function getSats(): array
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getSats();
    }

    public function getSpeedKmh(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getSpeedKmh();
    }

    public function getSpeedKnots(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getSpeedKnots();
    }

    public function getTimestampUtc(): ?DateTimeImmutable
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getTimestampUtc();
    }

    public function getTotalMessNum(): ?int
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getTotalMessNum();
    }

    public function getTrackAngleDeg(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getTrackAngleDeg();
    }

    public function getVdop(): ?float
    {
        /** @var GlobalPositioningSystem $circuit */
        $circuit = &$this->sensor;

        return $circuit->getVdop();
    }

    public static function as(SensorChip $circuit): static
    {
        $attr = reflect_class($circuit, TriangulatesPosition::class);
        if ($attr->getName() == TriangulatesPosition::class) {
            return new static($circuit);
        }

        throw SensorException::missingRequiredAbility('GPSSensor', $circuit::class, 'TriangulatesPosition');
    }
}
