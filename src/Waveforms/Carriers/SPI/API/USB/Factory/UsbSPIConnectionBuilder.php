<?php

namespace Waveforms\Carriers\SPI\API\USB\Factory;

use Exception;
use Microscrap\Bindings\FTDI\Enums\FtdiProductId;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEInterface;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Waveforms\Carriers\SPI\API\USB\Exceptions\UsbSPIException;
use Waveforms\Carriers\SPI\API\USB\UsbSPIDevice;
use Waveforms\Carriers\SPI\Enums\SPIEndianness;
use Waveforms\Carriers\SPI\Factory\SPIConnectionBuilder;

class UsbSPIConnectionBuilder extends SPIConnectionBuilder
{
    private ?FtdiProductId $device = null;

    /** Zero-based MPSSE channel index, treated like a native spidev chip-select. */
    private int $channel = 0;

    /** True once an interface has been pinned explicitly via {@see self::interface()}. */
    private bool $iface_explicit = false;

    public MPSSEInterface $iface = MPSSEInterface::IFACE_A;

    /**
     * @throws Exception
     */
    public function firstly(int|string $master): static
    {
        if (is_int($master)) {
            throw new Exception(static::class.' requires the device to be a string.');
        }

        return $this->device($master);
    }

    public function device(string $device): static
    {
        $device = strtoupper($device);
        if (mpsse_check_ftdi_device($device)) {
            $this->device = FtdiProductId::{$device};

            return $this;
        }

        throw UsbSPIException::unsupportedDevice($device);
    }

    public function chip(int $chip_select): static
    {
        return $this->channel($chip_select);
    }

    public function channel(int $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function interface(MPSSEInterface $iface): static
    {
        $this->iface = $iface;
        $this->iface_explicit = true;

        return $this;
    }

    public function endianness(SPIEndianness $endianness): static
    {
        $this->endianness = $endianness;

        return $this;
    }

    public function boot(): UsbSPIDevice
    {
        if (is_null($this->device)) {
            throw UsbSPIException::missingDevice();
        }

        if (! $this->iface_explicit) {
            $this->iface = $this->resolveInterface($this->channel);
        }

        $error = '';
        $usb_context = mpsse_open(
            vid: FtdiVendorId::FTDI->value,
            pid: $this->device->value,
            mode: MPSSEMode::from($this->spi_mode->value + 1),
            freq: $this->speed,
            endianness: MPSSEEndianness::from($this->endianness->value),
            iface: $this->iface,
            error: $error,
        );

        if (! is_null($usb_context)) {
            return new UsbSPIDevice($usb_context);
        }

        throw UsbSPIException::couldNotOpenDeviceContext($this->device->name, $error);
    }

    public function connection(): string
    {
        return 'usb';
    }

    /**
     * Map a zero-based chip-select index to the MPSSE channel (interface) that
     * exposes its own SCK/DO/DI/CS pin group, mirroring native spidev where the
     * chip-select selects the hardware bus. Channel 0 is the first MPSSE engine
     * (ADBUS / IFACE_A), channel 1 the second (BDBUS / IFACE_B), and so on.
     */
    private function resolveInterface(int $channel): MPSSEInterface
    {
        $available = $this->mpsseChannelCount($this->device);

        if ($channel < 0 || $channel >= $available) {
            throw UsbSPIException::unsupportedChannel($this->device->name, $channel, $available);
        }

        // IFACE_A = 1, so a zero-based index maps with a +1 offset and never yields IFACE_ANY (0).
        return MPSSEInterface::from($channel + 1);
    }

    /**
     * Number of MPSSE-capable channels per FTDI device. The FT2232H and FT4232H
     * expose two MPSSE engines (channels A and B); every other supported part is
     * single-channel.
     */
    private function mpsseChannelCount(FtdiProductId $device): int
    {
        return match ($device) {
            FtdiProductId::FT2232, FtdiProductId::FT4232 => 2,
            default => 1,
        };
    }
}
