<?php

namespace BareMetal\Circuits;

use BareMetal\Circuits\Managers\I2CManager;
use GPIO\Common\GPIOCarriers;
use GPIO\Common\LoadDefaultProtocolManagers;
use ScrapyardIO\NutsAndBolts\ServiceProvider;

class GPIOProtocolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootGpioCarriers();

        I2CManager::register($this->app);
    }

    /**
     * Composer apps keep microscrap under vendor/; monorepo GPIO keeps a sibling folder.
     * Core is the bridge — GPIO::i2c('usb') stays unchanged.
     */
    protected function bootGpioCarriers(): void
    {
        $vendor_microscrap = $this->app->basePath('vendor/microscrap');

        $managers = is_dir($vendor_microscrap)
            ? LoadDefaultProtocolManagers::run($vendor_microscrap)
            : LoadDefaultProtocolManagers::run();

        GPIOCarriers::boot($managers);
    }
}
