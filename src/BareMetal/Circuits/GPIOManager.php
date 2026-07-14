<?php

namespace BareMetal\Circuits;

use BareMetal\Circuits\Managers\DigitalInputManager;
use BareMetal\Circuits\Managers\DigitalOutputManager;
use BareMetal\Circuits\MagicAliases\I2CManager;
use BareMetal\Circuits\Managers\PWMManager;
use BareMetal\Circuits\Managers\SPIManager;
use BareMetal\Circuits\Managers\UARTManager;
use BareMetal\Contracts\Sensors\Sensor as SensorInterface;
use BareMetal\Contracts\Circuits\CircuitFactory as FactoryContract;
use BareMetal\Contracts\Core\Machine;
use BareMetal\Contracts\Sensors\SensorComponent as SensorComponentInterface;
use BareMetal\Contracts\Sensors\SensorException;

class GPIOManager implements FactoryContract
{
    /**
     * The application instance.
     */
    protected Machine $app;

    /**
     * @throws SensorException
     */
    public static function __callStatic($method, $parameters)
    {
        return match($method) {
            'findMainProtocol' => static::findMainProtocol($parameters[0]),
            'manage' => static::manage(...$parameters),
            default => throw new SensorException("Method $method does not exist."),
        };
    }

    protected static array $supported_protocols = ['spi', 'i2c', 'uart', 'digital-in', 'digital-out', 'pwm'];

    private static function findMainProtocol(array $configs): string|false
    {
        $found = false;
        foreach(static::$supported_protocols as $protocol) {
            if(isset($configs[$protocol]))
            {
                $found = $protocol;
                break;
            }
        }

        return $found;
    }

    /**
     * @throws SensorException
     */
    private static function manage(string $protocol, string $driver, array $config, SensorComponentInterface $component): ?SensorInterface
    {
        return match($protocol) {
            'i2c' => I2CManager::driver($driver)->setup($config, $component),
            //'spi' => SPIManager::driver($driver)->setup($config, $component),
            //'pwm' => PWMManager::driver($driver)->setup($config, $component),
            //'uart' => UARTManager::driver($driver)->setup($config, $component),
            //'digital-in' => DigitalInputManager::driver($driver)->setup($config, $component),
            //'digital-out' => DigitalOutputManager::driver($driver)->setup($config, $component),
            'default' => throw new SensorException("Driver $driver does not exist."),
        };
    }
}
