<?php

namespace BareMetal\Sensors\Acceleration;

use BareMetal\Contracts\Core\Machine;
use BareMetal\Contracts\Sensors\Accelerometry\Accelerometer as AccelerometerInterface;
use BareMetal\Contracts\Sensors\SensorService;
use BareMetal\Contracts\Support\DeferrableProvider;
use ScrapyardIO\NutsAndBolts\ServiceProvider;

class AccelerationSensorsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        if (! config('accelerometers.enabled', false)) {
            return;
        }

        // Bindings for deferred providers must live in register(), not boot().
        $this->app->bind('accelerometer', AccelerometerInterface::class);
        $this->app->singleton(AccelerometerInterface::class, fn (Machine $app) => new Accelerometer(
            sensor_service: $app->make(SensorService::class),
        ));
    }

    /**
     * Services this deferred provider is responsible for.
     */
    public function provides(): array
    {
        return [
            'accelerometer',
            AccelerometerInterface::class,
        ];
    }
}
