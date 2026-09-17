<?php

namespace GeneralPurposeIO\Core\Providers;


use Composer\InstalledVersions;
use GeneralPurposeIO\Analog\AnalogServiceProvider;
use GeneralPurposeIO\Circuits\CircuitsServiceProvider;
use GeneralPurposeIO\Common\GPIOProtocolManager;
use GeneralPurposeIO\Contracts\Core\GPIOProtocolFactory as FactoryContract;
use GeneralPurposeIO\Contracts\Core\GPIOResourceDriver as ResourceContract;
use GeneralPurposeIO\Core\IOPools\GPIOResourceDriver;
use GeneralPurposeIO\Digital\DigitalIOServiceProvider;
use GeneralPurposeIO\I2C\I2CServiceProvider;
use GeneralPurposeIO\PWM\PWMServiceProvider;
use GeneralPurposeIO\SPI\SPIServiceProvider;
use GeneralPurposeIO\UART\UARTServiceProvider;
use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\AggregateServiceProvider;
use Voyager\System\Console\AboutCommand;

/** Eager on purpose: boot() must register the gpio dock resource without anyone resolving 'gpio' first. */
class ScrapyardIOServiceProvider extends AggregateServiceProvider
{
    protected array $providers = [
        UARTServiceProvider::class,
        DigitalIOServiceProvider::class,
        SPIServiceProvider::class,
        I2CServiceProvider::class,
        PWMServiceProvider::class,
        //CircuitsServiceProvider::class,
        //AnalogServiceProvider::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 4).'/config/gpio.php', 'gpio');

        //$this->app->singleton('gpio', fn (Vessel $app) => new GPIOProtocolManager($app));
        //$this->app->alias('gpio', GPIOProtocolManager::class);
        //$this->app->alias('gpio', FactoryContract::class);

        parent::register();
    }

    public function boot(): void
    {
        $this->publishes([dirname(__DIR__, 4).'/config/gpio.php' => $this->app->configPath('gpio.php')], 'gpio-config');

        //$this->registerAboutSection();
        $this->registerDockResource();
    }

    /** Register the gpio resource on the IOPool dock, when the dock is present and gpio.io_pools.enabled. */
    protected function registerDockResource(): void
    {
        if (! config('gpio.io_pools.enabled', true) || ! $this->app->bound('io-pool')) {
            return;
        }

        $dock = $this->app->make('io-pool');
        $cap = config('gpio.io_pools.defer_per_tick');
        $resource = new GPIOResourceDriver($dock, is_null($cap) ? null : (int) $cap);

        $dock->resource('gpio', $resource);
        $this->app->instance('gpio', $resource);
        $this->app->alias('gpio', ResourceContract::class);
    }

    /**
     * Contribute protocol inventory to Workshop `about`.
     */
    protected function registerAboutSection(): void
    {
        if (! class_exists(AboutCommand::class)) {
            return;
        }

        AboutCommand::add('GPIO', fn (): array => $this->gpioAboutRows());
    }

    /**
     * @return array<string, mixed>
     */
    protected function gpioAboutRows(): array
    {
        $usbUart = $this->microscrapIsInstalled('microscrap/ftdi');
        $usbMpsse = $this->microscrapIsInstalled('microscrap/mpsse');
        $hasPosix = $this->microscrapIsInstalled('microscrap/posix');

        $posixUart = $hasPosix && $this->microscrapIsInstalled('microscrap/uart');
        $posixDigital = $hasPosix && $this->microscrapIsInstalled('microscrap/gpio');
        $posixI2c = $hasPosix && $this->microscrapIsInstalled('microscrap/i2c');
        $posixSpi = $hasPosix && $this->microscrapIsInstalled('microscrap/spi');

        return [
            'digital-io' => $this->formatAboutAdapters($usbMpsse, $posixDigital),
            'analog-io' => 'none',
            'uart' => $this->formatAboutAdapters($usbUart, $posixUart),
            'pwm' => is_dir('/sys/class/pwm') ? 'native' : 'none',
            'i2c' => $this->formatAboutAdapters($usbMpsse, $posixI2c),
            'spi' => $this->formatAboutAdapters($usbMpsse, $posixSpi),
        ];
    }

    /**
     * Format adapter availability for About (usb green, posix yellow, dual like Drivers→Logs).
     */
    protected function formatAboutAdapters(bool $usb, bool $posix): mixed
    {
        if (! $usb && ! $posix) {
            return 'none';
        }

        if ($usb && $posix) {
            return AboutCommand::format(
                value: ['usb', 'posix'],
                console: fn () => '<fg=green;options=bold>usb</> <fg=gray;options=bold>/</> <fg=yellow;options=bold>posix</>',
                json: fn (array $value) => $value,
            );
        }

        if ($usb) {
            return AboutCommand::format(
                value: 'usb',
                console: fn (string $value) => '<fg=green;options=bold>'.$value.'</>',
            );
        }

        return AboutCommand::format(
            value: 'posix',
            console: fn (string $value) => '<fg=yellow;options=bold>'.$value.'</>',
        );
    }

    protected function microscrapIsInstalled(string $package): bool
    {
        return class_exists(InstalledVersions::class)
            && InstalledVersions::isInstalled($package);
    }
}
