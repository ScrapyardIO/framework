<?php

namespace GeneralPurposeIO\Core\Providers;

use GeneralPurposeIO\Digital\DigitalIOServiceProvider;
use GeneralPurposeIO\I2C\I2CServiceProvider;
use GeneralPurposeIO\SPI\SPIServiceProvider;
use Voyager\NutsAndBolts\AggregateServiceProvider;

class ScrapyardIOServiceProvider extends AggregateServiceProvider
{
    protected array $providers = [
        DigitalIOServiceProvider::class,
        I2CServiceProvider::class,
        SPIServiceProvider::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 4).'/config/gpio.php', 'gpio');

        parent::register();
    }

    public function boot(): void
    {
        $this->publishes([dirname(__DIR__, 4).'/config/gpio.php' => $this->app->configPath('gpio.php')], 'gpio-config');
    }
}
