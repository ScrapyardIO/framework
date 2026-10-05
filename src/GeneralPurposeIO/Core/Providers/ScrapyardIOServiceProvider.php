<?php

namespace GeneralPurposeIO\Core\Providers;

use GeneralPurposeIO\Core\Console\ExtensionInstallCommand;
use GeneralPurposeIO\Core\Console\ModuleInstallCommand;
use GeneralPurposeIO\I2C\I2CServiceProvider;
use GeneralPurposeIO\PWM\PWMServiceProvider;
use GeneralPurposeIO\SPI\SPIServiceProvider;
use GeneralPurposeIO\UART\UARTServiceProvider;
use Voyager\NutsAndBolts\AggregateServiceProvider;
use GeneralPurposeIO\Digital\DigitalIOServiceProvider;
use GeneralPurposeIO\IntegratedCircuits\IntegratedCircuitsServiceProvider;

class ScrapyardIOServiceProvider extends AggregateServiceProvider
{
    protected array $providers = [
        DigitalIOServiceProvider::class,
        I2CServiceProvider::class,
        SPIServiceProvider::class,
        PWMServiceProvider::class,
        UARTServiceProvider::class,
        IntegratedCircuitsServiceProvider::class,
    ];

    /**
     * Dev commands: scrapyard:ext installs ext-posi and ext-ftdi through PIE, scrapyard:modules the microscrap
     * packages the machine can run.
     *
     * @var list<class-string>
     */
    protected array $dev_commands = [
        ExtensionInstallCommand::class,
        ModuleInstallCommand::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 4).'/config/gpio.php', 'gpio');
        $this->registerCommands();

        parent::register();
    }

    /**
     * The console loads commands through Symfony's ContainerCommandLoader, which only lists a command the container
     * has bound, so each one is bound before it goes on the list.
     */
    protected function registerCommands(): void
    {
        foreach ($this->dev_commands as $command) {
            $this->app->registerSingleton($command);
        }

        $this->commands($this->dev_commands);
    }

    public function boot(): void
    {
        $this->publishes([dirname(__DIR__, 4).'/config/gpio.php' => $this->app->configPath('gpio.php')], 'gpio-config');
    }
}
