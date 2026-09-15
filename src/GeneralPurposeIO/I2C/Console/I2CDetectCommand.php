<?php

namespace GeneralPurposeIO\I2C\Console;

use Voyager\Console\Command;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\I2C\I2C;
use GeneralPurposeIO\I2C\I2CBusScanner;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'i2cdetect')]
class I2CDetectCommand extends Command
{
    protected ?string $signature = 'i2cdetect
                    {adapter : I2C adapter name (posix, usb)}
                    {device : Bus number for posix, or device name for usb (e.g. ft232h)}';

    protected string $description = 'Scan an I2C bus for devices (like i2cdetect -q -y)';

    public function handle(): int
    {
        $adapter = strtolower((string) $this->argument('adapter'));
        $device = (string) $this->argument('device');

        try {
            $factory = $this->resolveFactory($adapter, $device);
            $driver = $factory->driver();
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $posixBus = $adapter === 'posix' ? (int) $device : null;

        try {
            $scanner = new I2CBusScanner($driver, $posixBus);
            $this->output->write($scanner->render());
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        } finally {
            $driver->close();
        }

        return self::SUCCESS;
    }

    /**
     * @throws I2CException
     */
    protected function resolveFactory(string $adapter, string $device): object
    {
        $communication = I2C::adapter($adapter);

        return match ($adapter) {
            'posix' => $communication->device((int) $device),
            'usb' => $communication->device($device),
            default => $communication->device(
                ctype_digit($device) ? (int) $device : $device,
            ),
        };
    }
}
