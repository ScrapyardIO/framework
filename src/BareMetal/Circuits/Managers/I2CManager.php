<?php

namespace BareMetal\Circuits\Managers;

use BareMetal\Contracts\Core\Machine;
use GPIO\Common\GPIO;
use ScrapyardIO\NutsAndBolts\Manager;

class I2CManager extends Manager
{
    public function createUsbDriver()
    {
        // @todo - check that i2c is enabled in the config
        $factory = GPIO::i2c('usb');
        dd("Pick up here baby!", $factory);

    }

    public function createPosixDriver()
    {
        // @todo - check that i2c is enabled in the config
        $factory = GPIO::i2c('posix');
    }

    public function getDefaultDriver(): ?string
    {
        // @todo - change this in config scrapyard-io
        return config('scrapyard-io.protocols.i2c.default_driver','posix');
    }

    public static function register(Machine $app): void
    {
        $app->singleton(I2CManager::class, fn(Machine $app) => new static($app));
    }
}
