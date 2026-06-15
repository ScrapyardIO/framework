<?php

namespace RealityInterface\Sensors\Contracts\Applied\GPS;

use DateTimeImmutable;
use RealityInterface\Sensors\Enums\GpsFixQuality;

/**
 * Common surface shared by every NMEA GPS chip (MTK3339, GY-NEO6M, ...). Only
 * the methods whose signatures are identical across drivers live here; driver
 * specific readings that return vendor enums (fix quality, 3D fix, selection
 * mode) are intentionally left off the contract.
 */
interface GlobalPositioningSystem
{
    /**
     * Pull the next NMEA sentence off the wire and fold it into the fix.
     */
    public function update(): bool;

    /**
     * Send a command string to the GPS, optionally appending the NMEA checksum.
     */
    public function sendCommand(string $command, bool $add_checksum = true): void;

    /**
     * Read up to $num_bytes of raw data without parsing.
     *
     * @return list<int>
     */
    public function read(int $num_bytes): array;

    /**
     * Return the next newline-terminated sentence, or null if none is buffered.
     */
    public function readLine(): ?string;

    /**
     * Write raw data to the GPS without parsing or checksums.
     *
     * @param  string|list<int>  $data
     */
    public function write(string|array $data): int;

    /**
     * Driver-agnostic fix quality, mapped from each chip's vendor enum.
     */
    public function fixQuality(): ?GpsFixQuality;

    public function getAltitudeM(): ?float;

    public function getDatetime(): ?DateTimeImmutable;

    public function getDebug(): bool;

    public function setDebug(bool $debug): void;

    public function getHas3dFix(): bool;

    public function getHasFix(): bool;

    public function getHdop(): ?float;

    public function getHeightGeoid(): ?float;

    public function getHorizontalDilution(): ?float;

    public function getInWaiting(): int;

    public function getIsActiveData(): ?string;

    public function getLatitude(): ?float;

    public function getLatitudeDegrees(): ?int;

    public function getLatitudeMinutes(): ?float;

    public function getLongitude(): ?float;

    public function getLongitudeDegrees(): ?int;

    public function getLongitudeMinutes(): ?float;

    public function getMessNum(): ?int;

    public function getNmeaSentence(): ?string;

    public function getPdop(): ?float;

    /**
     * @return list<int>
     */
    public function getSatPrns(): array;

    public function getSatellites(): ?int;

    public function getSatellitesPrev(): ?int;

    /**
     * @return array<int, object>
     */
    public function getSats(): array;

    public function getSpeedKmh(): ?float;

    public function getSpeedKnots(): ?float;

    public function getTimestampUtc(): ?DateTimeImmutable;

    public function getTotalMessNum(): ?int;

    public function getTrackAngleDeg(): ?float;

    public function getVdop(): ?float;
}
