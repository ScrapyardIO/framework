<?php

namespace GPIO\Common;

use ReflectionException;
use GPIO\Contracts\Common\GPIOException;
use ScrapyardIO\NutsAndBolts\ScrapyardLibrary;
use GPIO\Contracts\I2C\I2CConnectionFactory as I2CConnectionFactoryInterface;
use GPIO\Contracts\PWM\PWMConnectionFactory as PWMConnectionFactoryInterface;
use GPIO\Contracts\SPI\SPIConnectionFactory as SPIConnectionFactoryInterface;
use GPIO\Contracts\UART\UARTConnectionFactory as UARTConnectionFactoryInterface;
use GPIO\Contracts\Common\GPIOConnectionFactory as GPIOConnectionFactoryInterface;
use GPIO\Contracts\Digital\DigitalInputConnectionFactory as DigitalInputConnectionFactoryInterface;
use GPIO\Contracts\Digital\DigitalOutputConnectionFactory as DigitalOutputConnectionFactoryInterface;

/**
 * @method static DigitalInputConnectionFactoryInterface digitalIn(string $driver)
 * @method static DigitalOutputConnectionFactoryInterface digitalOut(string $driver)
 * @method static I2CConnectionFactoryInterface i2c(string $driver)
 * @method static PWMConnectionFactoryInterface pwm(string $driver)
 * @method static SPIConnectionFactoryInterface spi(string $driver)
 * @method static UARTConnectionFactoryInterface uart(string $driver)
 */
class GPIO extends ScrapyardLibrary
{
    protected static ?self $instance = null;

    protected array $protocol_factories = [];

    /**
     * @throws GPIOException|ReflectionException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $gpio = self::boot();

        $protocol = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
        if(isset($gpio->protocolFactories()[$protocol])) {
            $driver = $arguments[0];
            $factory = $gpio->protocolFactories()[$protocol];

            /** @var GPIOConnectionFactoryInterface */
            return new $factory($driver);
        }

        throw GPIOException::invalidStaticMethod($name, static::class);
    }

    protected function protocolFactories(): array
    {
        return $this->protocol_factories;
    }

    public function factory(string $protocol, string $factory): void
    {
        $this->protocol_factories[$protocol] = $factory;
    }

    /**
     * @throws ReflectionException
     */
    public static function boot(?array $protocols = null): static
    {
        $instance = static::$instance;
        if($instance === null) {
            $self = new self();
            $protocols ??= LoadDefaultFactories::run();

            foreach($protocols as $protocol => $factory) {
                $self->factory($protocol, $factory);
            }
            $instance = self::$instance = $self;
        }

        return $instance;
    }

    protected static function getInstance(): self
    {
        return self::$instance;
    }
}
