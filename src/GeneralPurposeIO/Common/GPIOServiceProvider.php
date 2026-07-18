<?php

namespace GeneralPurposeIO\Common;

use Fabricate\Contracts\Support\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use GeneralPurposeIO\Contracts\Common\GPIOProtocolFactory as FactoryContract;

class GPIOServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->machine->singleton('gpio', fn ($app) => new GPIOProtocolManager($app));
        $this->machine->alias('gpio', GPIOProtocolManager::class);
        $this->machine->alias('gpio', FactoryContract::class);
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'gpio',
            GPIOProtocolManager::class,
            FactoryContract::class,
        ];
    }
}
