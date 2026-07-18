<?php

namespace GeneralPurposeIO\I2C\Factory;

use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Digital\Factory\PosixDigitalPinDriverFactory;
use GeneralPurposeIO\I2C\Drivers\PosixI2CDriver;
use GeneralPurposeIO\I2C\I2C;
use GeneralPurposeIO\I2C\I2CConnectionBus;

class PosixI2CDriverFactory extends I2CDriverFactory
{
    protected function carrierAdapter(): string
    {
        return 'posix';
    }

    /**
     * @throws I2CException
     */
    public function create(): I2C|I2CConnectionBus
    {
        $this->assertReady();

        $bus = i2c_open("/dev/i2c-{$this->device}", $this->slave_address);

        if (is_null($bus)) {
            throw I2CException::couldNotOpenI2CDevice($this->device);
        }

        $driver = new PosixI2CDriver($bus, $this->slave_address);
        $i2c = new I2C($driver, $this->slave_address);

        if (count($this->digital_pins) === 0) {
            return $i2c;
        }

        if (is_null($this->gpio_chip)) {
            throw I2CException::missingGpioChipForDigitalPins();
        }

        /** @var list<PosixDigitalPinDriverFactory> $factories */
        $factories = $this->digital_pins;

        $pin_bus = PosixDigitalPinDriverFactory::bundle(
            (int) $this->gpio_chip,
            sprintf('i2c-0x%02x', $this->slave_address),
            $factories,
        );

        return new I2CConnectionBus($i2c, $pin_bus->pins, shares_i2c_context: false);
    }
}
