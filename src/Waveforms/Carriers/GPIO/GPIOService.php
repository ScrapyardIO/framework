<?php

namespace Waveforms\Carriers\GPIO;

use Exception;
use Waveforms\Carriers\GPIO\API\Native\Factory\NativeGPIOConnectionBuilder;
use Waveforms\Carriers\GPIO\API\Native\NativeGPIOInput;
use Waveforms\Carriers\GPIO\API\Native\NativeGPIOOutput;
use Waveforms\Carriers\GPIO\API\USB\Factory\USBGPIOConnectionBuilder;
use Waveforms\Carriers\GPIO\API\USB\UsbGPIOInput;
use Waveforms\Carriers\GPIO\API\USB\UsbGPIOOutput;
use Waveforms\Carriers\GPIO\Contracts\GPIOInput;
use Waveforms\Carriers\GPIO\Contracts\GPIOOutput;
use Waveforms\CarrierService;

class GPIOService extends CarrierService
{
    protected array $drivers = [
        'native' => [
            'factory' => NativeGPIOConnectionBuilder::class,
            'input' => NativeGPIOInput::class,
            'output' => NativeGPIOOutput::class,
        ],
        'usb' => [
            'factory' => USBGPIOConnectionBuilder::class,
            'input' => UsbGPIOInput::class,
            'output' => UsbGPIOOutput::class,
        ],
    ];

    /**
     * @throws Exception
     */
    public function getInput(string $driver, int $pin): GPIOInput
    {
        if ($this->isValidDriver($driver)) {
            return new $this->drivers[$driver]['input']($pin);
        }

        throw new Exception("Invalid driver: $driver");
    }

    /**
     * @throws Exception
     */
    public function getOutput(string $driver, int $pin): GPIOOutput
    {
        if ($this->isValidDriver($driver)) {
            return new $this->drivers[$driver]['output']($pin);
        }

        throw new Exception("Invalid driver: $driver");
    }
}
